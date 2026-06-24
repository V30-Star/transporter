<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingMutasiStokController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get(['fcabangkode', 'fcabangname']);
        $warehouses = DB::table('mswh')->where('fnonactive', '0')->orderBy('fwhcode')->get(['fwhcode', 'fwhname']);
        $products = DB::table('msprd')->orderBy('fprdcode')->get(['fprdcode', 'fprdname']);

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingmutasistok.index', compact('branches', 'warehouses', 'products', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type = $request->input('display_type', 'detail');

        return view('listingmutasistok.print', [
            'groupedData' => $results->groupBy('fstockmtno'),
            'type' => $type,
            'request' => $request,
            'user_session' => auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type = $request->input('display_type', 'detail');
        $groupedData = $results->groupBy('fstockmtno');

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Writer();
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontColor: 'C00000');
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');

        $makeRow = function (array $values, ?Style $style = null) use ($writer): void {
            $cells = array_map(fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value), $values);
            $writer->addRow(new Row($cells));
        };

        $makeRow(['LISTING MUTASI STOK'], $styleTitle);
        $makeRow(['Periode: ' . ($request->date_from ?? '...') . ' s/d ' . ($request->date_to ?? '...')]);
        $makeRow(['Cabang: ' . (is_array($request->branch_codes ?? null) ? implode(', ', $request->branch_codes) : 'Semua')]);
        $makeRow(['Gudang Asal: ' . ($request->warehouse_from ?: 'Semua') . ' | Gudang Tujuan: ' . ($request->warehouse_to ?: 'Semua')]);
        $makeRow([]);

        $header = ['Cab.', 'No.Transaksi', 'Tanggal', 'Dari', 'Ke', 'Keterangan', 'User-Id'];
        if ($type === 'detail') {
            $header = array_merge($header, ['Kode Produk', 'Nama Barang', 'Quantity']);
        }
        $makeRow($header, $styleHeader);

        foreach ($groupedData as $details) {
            $h = $details->first();
            $headerValues = [
                $h->fbranchcode,
                $h->fstockmtno,
                $h->fstockmtdate,
                $h->ffrom,
                $h->fto,
                $h->fket,
                $h->fuserid,
            ];

            if ($type === 'detail') {
                foreach ($details as $detail) {
                    $makeRow(array_merge($headerValues, [
                        $detail->fprdcode,
                        $detail->fprdname,
                        (float) $detail->fqty,
                    ]));
                }
            } else {
                $makeRow($headerValues);
            }
        }

        $writer->close();

        return response()->download($tempFile, 'listing_mutasi_stok_' . date('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    private function buildQuery(Request $request)
    {
        $query = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->where('m.fstockmtcode', 'MUT')
            ->selectRaw("\n                m.fbranchcode,\n                
            m.fstockmtno,\n                m.fstockmtdate,\n                
            m.fket,\n                m.ffrom,\n                m.fto,\n                
            m.fusercreate,\n                d.fprdcode,\n                p.fprdname,\n                
            d.fqty,\n                d.fsatuan,\n                d.fprice,\n                
            d.ftotprice,\n                m.famount\n            ");

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = array_values(array_filter((array) $request->input('branch_codes', [])));
        if ($branches !== []) {
            $query->whereIn('m.fbranchcode', $branches);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('m.fstockmtdate', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('m.fstockmtdate', '<=', $request->date_to);
        }

        if ($request->filled('warehouse_from')) {
            $query->where('m.ffrom', $request->warehouse_from);
        }

        if ($request->filled('warehouse_to')) {
            $query->where('m.fto', $request->warehouse_to);
        }

        $selectedProducts = trim((string) $request->input('selected_products', ''));
        if ($selectedProducts !== '') {
            $query->whereIn('d.fprdcode', array_values(array_filter(explode(',', $selectedProducts))));
        }

        return $query
            ->orderBy('m.fbranchcode')
            ->orderBy('m.fstockmtno')
            ->orderBy('d.fprdcode');
    }
}
