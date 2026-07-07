<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingReturPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();
        $salesmen = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingreturpenjualan.index', compact(
            'branches', 'customers', 'salesmen', 'products', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);

        return view('listingreturpenjualan.print', [
            'results' => $results,
            'detailMode' => $request->boolean('detail', true),
            'rekapMode' => $request->boolean('rekap', false),
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $query = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('mswh as w', 'm.ffrom', '=', 'w.fwhcode')
            ->where('m.ftrcode', 'REJ')
            ->select(
                'm.ftranmtid as fstockmtid',
                'm.fsono as fstockmtno',
                'm.fsodate as fstockmtdate',
                'm.fcustno as fsupplier',
                'c.fcustomername as fcustname',
                DB::raw("'' as fcity"),
                'm.fket',
                'm.frefno',
                DB::raw("CAST(CONCAT(TRIM(m.ffrom), ' - ', TRIM(w.fwhname)) AS VARCHAR(50)) as gudang"),
                'm.fuserid',
                'd.fprdcode',
                'p.fprdname',
                'd.frefcode as frefdtno',
                DB::raw('COALESCE(d.fqty, 0) as fqty'),
                'd.fsatuan',
                DB::raw('COALESCE(d.fprice, 0) as fprice'),
                DB::raw('COALESCE(d.famount, 0) as ftotprice'),
                DB::raw('COALESCE(m.famountso, 0) as famountmt'),
                DB::raw('COALESCE(m.famountgross, 0) as famount'),
                DB::raw('COALESCE(m.famountpajak, 0) as famountpajak')
            );

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $query->whereIn('m.fbranchcode', (array) $branches);
        }

        if ($request->date_from) {
            $query->where('m.fsodate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fsodate', '<=', $request->date_to);
        }

        if ($request->cust_from && $request->cust_to) {
            $query->whereBetween(DB::raw('TRIM(m.fcustno)'), [$request->cust_from, $request->cust_to]);
        }

        if ($request->salesman_code) {
            $query->where('m.fsalesman', $request->salesman_code);
        }

        $selectedProducts = $request->input('selected_products', '');
        if (!empty($selectedProducts)) {
            $query->whereIn('d.fprdcode', explode(',', $selectedProducts));
        }

        return $query
            ->orderBy('m.fsodate', 'asc')
            ->orderBy('m.fsono', 'asc')
            ->orderBy('d.fprdcode', 'asc')
            ->get();
    }

    public function exportExcel(Request $request)
    {
        $results = $this->getRawData($request);
        $grouped = $results->groupBy('fstockmtid');
        $detailMode = $request->boolean('detail', true);

        $filename = 'Listing_Retur_Penjualan_'.date('YmdHis').'.xlsx';
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
        $writer->addRow($makeRow(['LISTING RETUR PENJUALAN'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No. Transaksi', 'Tanggal', 'Nama Customer', 'Keterangan', 'Total Harga', 'PPN', 'Total Retur', 'User-Id'
        ], $styleHeader));

        if ($detailMode) {
            $writer->addRow($makeRow([
                '', 'Kode Barang', 'Nama Barang', 'Ref. Faktur#', 'Quantity', 'Satuan', '@ Harga', 'Total Harga Detail'
            ], $styleHeader));
        }

        $grandHarga = 0;
        $grandPpn = 0;
        $grandRetur = 0;

        foreach ($grouped as $items) {
            $h = $items->first();
            $totalHarga = (float) $h->famount;
            $ppn = (float) $h->famountpajak;
            $totalRetur = (float) $h->famountmt;

            $grandHarga += $totalHarga;
            $grandPpn += $ppn;
            $grandRetur += $totalRetur;

            $writer->addRow($makeRow([
                $h->fstockmtno,
                $h->fstockmtdate ? date('d/m/Y', strtotime($h->fstockmtdate)) : '',
                $h->fcustname,
                $h->fket,
                $totalHarga,
                $ppn,
                $totalRetur,
                $h->fuserid
            ], $styleInvoice));

            if ($detailMode) {
                foreach ($items as $d) {
                    $writer->addRow($makeRow([
                        '',
                        $d->fprdcode,
                        $d->fprdname,
                        $d->frefdtno,
                        (float) $d->fqty,
                        $d->fsatuan,
                        (float) $d->fprice,
                        (float) $d->ftotprice
                    ], $styleDetail));
                }
            }
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL', '', '', '',
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
