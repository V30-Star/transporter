<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingReturPembelianController extends Controller
{
    public function index()
    {
        $products = DB::table('msprd')->orderBy('fprdcode')->get();
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingreturpembelian.index', compact(
            'products', 'branches', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);

        return view('listingreturpembelian.print', [
            'results' => $results,
            'detailMode' => $request->boolean('detail', true),
            'rekapMode' => $request->boolean('rekap', false),
            'user_session' => auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $query = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsuppliercode')
            ->leftJoin('mswh as wh', 'm.ffrom', '=', 'wh.fwhcode')
            ->where('m.fstockmtcode', 'REB')
            ->select(
                'm.fstockmtid',
                'm.fstockmtno',
                'm.fstockmtdate',
                'm.fbranchcode',
                'm.fsupplier',
                's.fsuppliername',
                'm.ffrom',
                'm.fket',
                'm.fusercreate',
                DB::raw('COALESCE(m.famount, 0) as header_amount'),
                DB::raw('COALESCE(m.famountpajak, 0) as header_ppn'),
                DB::raw('COALESCE(m.famountmt, 0) as header_total'),
                'wh.fwhname',
                'd.fprdcode',
                DB::raw('COALESCE(d.fqtykecil, 0) as fqtykecil'),
                DB::raw('COALESCE(d.fqty, 0) as fqty'),
                DB::raw('COALESCE(d.fprice, 0) as fprice'),
                DB::raw('COALESCE(d.ftotprice, 0) as famount'),
                'd.fsatuan',
                'p.fprdcode',
                'p.fprdname'
            );

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $query->whereIn('m.fbranchcode', (array) $branches);
        }

        if ($request->date_from) {
            $query->where('m.fstockmtdate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fstockmtdate', '<=', $request->date_to);
        }

        $selectedProducts = $request->input('selected_products', '');
        if (!empty($selectedProducts)) {
            $productCodes = explode(',', $selectedProducts);
            $query->whereIn('p.fprdcode', $productCodes);
        }

        $query->orderBy('m.fstockmtno', 'asc');

        return $query->get();
    }

    public function exportExcel(Request $request)
    {
        $results = $this->getRawData($request);
        $grouped = $results->groupBy('fstockmtid');
        $detailMode = $request->boolean('detail', true);

        $filename = 'Listing_Retur_Pembelian_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleInvoice = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleDetail = new Style(fontBold: false);
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['LISTING RETUR PEMBELIAN'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'Cab.', 'No. Transaksi', 'Tanggal', 'Supp#', 'Nama Supplier', 'Keterangan', 'Total Harga', 'PPN', 'Total Retur', 'User-Id'
        ], $styleHeader));

        if ($detailMode) {
            $writer->addRow($makeRow([
                '', '', 'Kode Barang', 'Nama Barang', 'Satuan', 'Quantity', '@ Harga', 'Total Harga Detail'
            ], $styleHeader));
        }

        $grandHarga = 0;
        $grandPpn = 0;
        $grandRetur = 0;

        foreach ($grouped as $items) {
            $h = $items->first();
            $totalHarga = (float) $h->header_amount;
            $ppn = (float) $h->header_ppn;
            $totalRetur = (float) $h->header_total;

            $grandHarga += $totalHarga;
            $grandPpn += $ppn;
            $grandRetur += $totalRetur;

            $writer->addRow($makeRow([
                $h->fbranchcode,
                $h->fstockmtno,
                $h->fstockmtdate ? date('d/m/Y', strtotime($h->fstockmtdate)) : '',
                $h->fsupplier,
                $h->fsuppliername,
                $h->fket,
                $totalHarga,
                $ppn,
                $totalRetur,
                $h->fusercreate
            ], $styleInvoice));

            if ($detailMode) {
                foreach ($items as $d) {
                    $writer->addRow($makeRow([
                        '',
                        '',
                        $d->fprdcode,
                        $d->fprdname,
                        $d->fsatuan,
                        (float) $d->fqty,
                        (float) $d->fprice,
                        (float) $d->famount
                    ], $styleDetail));
                }
            }
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL', '', '', '', '', '',
            $grandHarga,
            $grandPpn,
            $grandRetur,
            ''
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
