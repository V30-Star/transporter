<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

class ListingFakturPembelianController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();
        return view('listingfakturpembelian.index', compact('suppliers'));
    }

    public function print(Request $request)
    {
        $results     = $this->getRawData($request);
        $groupedData = $results->groupBy('fstockmtno');
        $chunkedData = $groupedData->chunk(4);

        $totalLaporan = $results->unique('fstockmtno')->sum('famountmt');

        return view('listingfakturpembelian.print', [
            'chunkedData'  => $chunkedData,
            'totalPages'   => $chunkedData->count(),
            'totalLaporan' => $totalLaporan,
            'user_session' => auth()->user()
        ]);
    }

    public function exportExcel(Request $request)
    {
        $results     = $this->getRawData($request);
        $groupedData = $results->groupBy('fstockmtno');

        $filename = "Listing_Faktur_Pembelian_" . date('YmdHis') . ".xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer();
        $writer->openToFile($tempFile);

        // --- Styles ---
        $styleTitle      = new Style(fontBold: true, fontSize: 14);
        $styleHeader     = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleMaster     = new Style(fontBold: true);
        $styleDetail     = new Style(fontColor: 'CC0000');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // --- Header Informasi ---
        $writer->addRow($makeRow(['LISTING FAKTUR PEMBELIAN'], $styleTitle));
        $writer->addRow($makeRow([
            'Supplier:',
            $request->sup_from
                ? '[' . $request->sup_from . '] s/d [' . $request->sup_to . ']'
                : 'Semua'
        ]));
        $writer->addRow($makeRow([
            'Periode:',
            ($request->date_from ?? '...') . ' s/d ' . ($request->date_to ?? '...')
        ]));
        $writer->addRow($makeRow([
            'Tipe Transaksi:',
            match ($request->type_transaksi) {
                '1'     => 'Trade',
                '2'     => 'Non Trade',
                default => 'Semua'
            }
        ]));
        $writer->addRow($makeRow([]));

        // --- Header Kolom ---
        $writer->addRow($makeRow([
            'No. Transaksi',
            'Tanggal',
            'Nama Supplier',
            'Tipe',
            'Total Harga',
            'PPN',
            'Total Faktur',
            'User-id',
            'Kode Barang',
            'Nama Barang',
            'Ref. PO',
            'Quantity',
            'Satuan',
            '@ Harga',
            'Biaya',
            'Total Harga Detail',
        ], $styleHeader));

        $totalLaporan = 0;

        foreach ($groupedData as $fstockmtno => $details) {
            $h       = $details->first();
            $isFirst = true;
            $totalLaporan += (float) $h->famountmt;

            foreach ($details as $d) {
                $writer->addRow($makeRow([
                    $isFirst ? $h->fstockmtno : '',
                    $isFirst ? Carbon::parse($h->fstockmtdate)->format('d/m/Y') : '',
                    $isFirst ? ($h->fsuppliername ?? '') : '',
                    $isFirst ? $h->ftype : '',
                    $isFirst ? (float) $h->famount : '',
                    $isFirst ? (float) $h->famountpajak : '',
                    $isFirst ? (float) $h->famountmt : '',
                    $isFirst ? trim($h->fusercreate) : '',
                    $d->fprdcode,
                    $d->fprdname,
                    $d->frefdtno ?? '',
                    (float) $d->fqty,
                    $d->fsatuan,
                    (float) $d->fprice,
                    (float) $d->fbiaya,
                    (float) $d->ftotprice,
                ], $isFirst ? $styleMaster : $styleDetail));

                $isFirst = false;
            }
        }

        // --- Grand Total ---
        $writer->addRow($makeRow([
            'TOTAL KESELURUHAN FAKTUR PEMBELIAN',
            '',
            '',
            '',
            '',
            '',
            (float) $totalLaporan,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ], $styleGrandTotal));

        $writer->addRow($makeRow(['*** Akhir Laporan ***'], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getRawData(Request $request)
    {
        $query = DB::table('trstockmt as m')
            ->leftJoin('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdid')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsupplierid')
            ->select(
                'm.fstockmtno',
                'm.fstockmtdate',
                'm.fsupplier',
                's.fsuppliername',
                'm.fusercreate',
                'm.famount',
                'm.famountpajak',
                'm.famountmt',
                'd.fprdcode',
                'p.fprdname',
                'd.frefdtno',
                'd.fqty',
                'd.fqtyremain',
                'd.fprice',
                'd.fbiaya',
                'd.ftotprice',
                'd.fsatuan',
                DB::raw("case when m.ftrancode='0' then 'Trade' else 'Non Trade' end as ftype")
            )
            ->where('m.fstockmtcode', 'BUY');

        if ($request->date_from) $query->where('m.fstockmtdate', '>=', $request->date_from);
        if ($request->date_to)   $query->where('m.fstockmtdate', '<=', $request->date_to . ' 23:59:59');

        if ($request->type_transaksi == '1') {
            $query->where('m.ftrancode', '0');
        } elseif ($request->type_transaksi == '2') {
            $query->where('m.ftrancode', '1');
        }

        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        if ($request->sort_by == '1') {
            $query->orderBy('m.fstockmtdate', 'asc')->orderBy('m.fstockmtno', 'asc')->orderBy('d.fstockdtid', 'asc');
        } elseif ($request->sort_by == '2') {
            $query->orderBy('s.fsuppliername', 'asc')->orderBy('m.fstockmtdate', 'asc');
        } else {
            $query->orderBy('m.fstockmtno', 'asc')->orderBy('d.fstockdtid', 'asc');
        }

        return $query->get();
    }
}
