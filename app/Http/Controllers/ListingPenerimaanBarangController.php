<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingPenerimaanBarangController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();
        $warehouses = DB::table('mswh')->where('fnonactive', '0')->orderBy('fwhcode')->get();

        return view('listingpenerimaanbarang.index', compact('suppliers', 'warehouses'));
    }

    /**
     * Fungsi Private untuk mengambil data agar logic query tidak duplikat
     */
    private function getRawData(Request $request)
    {
        // 1. Subquery untuk mengecek Qty yang sudah ditagih (BUY)
        $subBuy = DB::table('trstockdt')
            ->select(
                DB::raw('frefdtno::text as frefdtno_text'),
                DB::raw('fprdcode::text as fprdcode_text'),
                DB::raw('sum(fqtykecil) as fqtybuy')
            )
            ->where('fstockmtcode', 'BUY')
            ->groupBy('frefdtno', 'fprdcode');

        // 2. Query Utama
        $query = DB::table('trstockmt as m')
            ->leftJoin('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->leftJoin('msprd as p', 'd.fprdcodeid', '=', 'p.fprdid')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsuppliercode')
            ->leftJoin('mswh as w', 'm.ffrom', '=', 'w.fwhcode')
            ->leftJoinSub($subBuy, 'buy', function ($join) {
                $join->on(DB::raw('m.fstockmtno::text'), '=', DB::raw('buy.frefdtno_text'))
                    ->on(DB::raw('p.fprdcode::text'), '=', DB::raw('buy.fprdcode_text'))
                    ->on(DB::raw('d.frefdtno::text'), '=', DB::raw('buy.frefdtno_text'));
            })
            ->select(
                'm.fstockmtno',
                'm.fstockmtdate',
                'm.fusercreate',
                'm.famountmt',
                's.fsuppliername',
                's.fsuppliercode',
                'w.fwhname',
                'p.fprdcode',
                'p.fprdname',
                'd.frefdtno as frefpo',
                'd.fqty',
                'd.fprice',
                'd.ftotprice',
                'd.fsatuan',
                DB::raw('COALESCE(buy.fqtybuy, 0) as fqtybuy')
            )
            ->where('m.fstockmtcode', 'TER');

        // Filter Tanggal
        if ($request->date_from) {
            $query->where('m.fstockmtdate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fstockmtdate', '<=', $request->date_to.' 23:59:59');
        }

        // Filter Gudang
        if ($request->warehouse) {
            $query->where('m.ffrom', $request->warehouse);
        }

        // Filter Supplier
        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        // Filter Status Tagihan
        if ($request->status == '1') {
            $query->whereRaw('d.fqtykecil - COALESCE(buy.fqtybuy, 0) = 0');
        } elseif ($request->status == '2') {
            // Filter sisa TER berdasarkan fqtykecil dinonaktifkan.
        }

        // Urutan
        if ($request->sort_by == 'name') {
            $query->orderBy('m.fstockmtdate', 'asc')->orderBy('m.fstockmtno', 'asc');
        } else {
            $query->orderBy('m.fstockmtno', 'asc');
        }

        return $query->get();
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);
        $totalLaporan = $results->unique('fstockmtno')->sum('famountmt');
        $chunkedData = $results->groupBy('fstockmtno')->chunk(5);

        return view('listingpenerimaanbarang.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'totalLaporan' => $totalLaporan,
            'user_session' => auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $results = $this->getRawData($request);
        $dataGrouped = $results->groupBy('fstockmtno');

        $filename = 'Listing_Penerimaan_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        // --- Styles ---
        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleMaster = new Style(fontBold: false);
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );

            return new Row($cells);
        };

        // --- Header Informasi ---
        $writer->addRow($makeRow(['LISTING PENERIMAAN BARANG'], $styleTitle));
        $writer->addRow($makeRow([
            'Supplier:',
            $request->sup_from
                ? '['.$request->sup_from.'] s/d ['.$request->sup_to.']'
                : 'Semua',
        ]));
        $writer->addRow($makeRow([
            'Periode:',
            ($request->date_from ?? '...').' s/d '.($request->date_to ?? '...'),
        ]));
        $writer->addRow($makeRow([]));

        // --- Header Kolom ---
        $writer->addRow($makeRow([
            'No. Transaksi',
            'Tanggal',
            'Gudang',
            'Supplier',
            'User-id',
            'Kode Barang',
            'Nama Barang',
            'Ref PO',
            'Qty',
            'Harga Satuan',
            'Total Harga',
        ], $styleHeader));

        $totalKeseluruhan = 0;

        foreach ($dataGrouped as $fstockmtno => $details) {
            $h = $details->first();
            $totalKeseluruhan += $h->famountmt;

            $isFirstDetail = true;

            foreach ($details as $d) {
                $writer->addRow($makeRow([
                    $isFirstDetail ? $h->fstockmtno : '',                          // No. Transaksi
                    $isFirstDetail ? Carbon::parse($h->fstockmtdate)->format('d/m/Y') : '', // Tanggal
                    $isFirstDetail ? $h->fwhname : '',                             // Gudang
                    $isFirstDetail ? $h->fsuppliername : '',                       // Supplier
                    $isFirstDetail ? trim($h->fusercreate) : '',                   // User-id
                    $d->fprdcode,                                                   // Kode Barang
                    $d->fprdname,                                                   // Nama Barang
                    $d->frefpo,                                                     // Ref PO
                    (float) $d->fqty,                                              // Qty
                    (float) $d->fprice,                                            // Harga Satuan
                    (float) $d->ftotprice,                                         // Total Harga
                ], $styleMaster));

                $isFirstDetail = false;
            }
        }

        // --- Grand Total ---
        $writer->addRow($makeRow([
            'TOTAL KESELURUHAN PENERIMAAN',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            (float) $totalKeseluruhan,
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
