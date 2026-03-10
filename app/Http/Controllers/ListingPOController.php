<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

class ListingPOController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();
        return view('listingpo.index', compact('suppliers'));
    }

    public function print(Request $request)
    {
        $results     = $this->getRawData($request);
        $groupedData = $results->groupBy('fpono');
        $chunkedData = $groupedData->chunk(4);

        return view('listingpo.print', [
            'chunkedData'  => $chunkedData,
            'totalPages'   => $chunkedData->count(),
            'user_session' => auth()->user()
        ]);
    }

    public function exportExcel(Request $request)
    {
        $results     = $this->getRawData($request);
        $groupedData = $results->groupBy('fpono');

        $filename = "Listing_PO_" . date('YmdHis') . ".xlsx";
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
        $writer->addRow($makeRow(['LISTING ORDER PEMBELIAN'], $styleTitle));
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
        $writer->addRow($makeRow([]));

        // --- Header Kolom ---
        $writer->addRow($makeRow([
            'No. PO',
            'Tanggal',
            'Nama Supplier',
            'User-id',
            'Close?',
            'Produk#',
            'Nama Produk',
            'Satuan',
            'Qty. PO',
            'Qty. Terima',
            'Sisa',
            'Harga',
            'Total Amount',
        ], $styleHeader));

        $totalQtyPO     = 0;
        $totalQtyTerima = 0;
        $totalAmount    = 0;

        foreach ($groupedData as $fpono => $details) {
            $h       = $details->first();
            $isFirst = true;

            foreach ($details as $d) {
                $sisa = (float) $d->fqty - (float) $d->fqtyterima;

                $totalQtyPO     += (float) $d->fqty;
                $totalQtyTerima += (float) $d->fqtyterima;
                $totalAmount    += (float) $d->famount;

                $writer->addRow($makeRow([
                    $isFirst ? $h->fpono : '',
                    $isFirst ? Carbon::parse($h->fpodate)->format('d/m/Y') : '',
                    $isFirst ? ($h->fsuppliername ?? '') : '',
                    $isFirst ? trim($h->fusercreate) : '',
                    $isFirst ? ($h->fclose == '1' ? 'Y' : 'N') : '',
                    $d->fprdcode,
                    $d->fprdname,
                    $d->fsatuan,
                    (float) $d->fqty,
                    (float) $d->fqtyterima,
                    $sisa,
                    (float) $d->fprice,
                    (float) $d->famount,
                ], $isFirst ? $styleMaster : $styleDetail));

                $isFirst = false;
            }
        }

        // --- Grand Total ---
        $writer->addRow($makeRow([
            '*** Akhir Laporan ***',
            '',
            '',
            '',
            '',
            '',
            '',
            'TOTAL:',
            $totalQtyPO,
            $totalQtyTerima,
            $totalQtyPO - $totalQtyTerima,
            '',
            $totalAmount,
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getRawData(Request $request)
    {
        $subTerima = DB::table('trstockdt')
            ->select('frefdtno', 'fprdcode', 'fnouref', DB::raw('sum(fqtykecil) as fqtyterima'))
            ->where(function ($q) {
                $q->where('fstockmtcode', 'TER')
                    ->orWhere(function ($sq) {
                        $sq->where('fcode', 'P')->where('fstockmtcode', 'BUY');
                    });
            })
            ->groupBy('frefdtno', 'fprdcode', 'fnouref');

        $query = DB::table('tr_poh as h')
            ->leftJoin('tr_pod as d', 'h.fpohdid', '=', 'd.fpono')
            ->leftJoin('mssupplier as s', 'h.fsupplier', '=', 's.fsupplierid')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdid')
            ->leftJoinSub($subTerima, 'ter', function ($join) {
                $join->on('h.fpohdid', '=', 'ter.frefdtno')
                    ->on(DB::raw('ter.fprdcode'), '=', DB::raw('p.fprdid'))
                    ->on(DB::raw('ter.fnouref'), '=', DB::raw('d.fnou'));
            })
            ->select(
                'h.fpohdid',
                'h.fpono',
                'h.fpodate',
                'h.fusercreate',
                'h.fclose',
                's.fsuppliername',
                'p.fprdcode',
                'p.fprdname',
                'p.fqtykecil as p_qtykecil',
                'd.fqty',
                'd.fsatuan',
                'd.fprice',
                'd.famount',
                'd.fnou',
                DB::raw('COALESCE(ter.fqtyterima, 0) as fqtyterima')
            );

        if ($request->date_from) $query->where('h.fpodate', '>=', $request->date_from);
        if ($request->date_to)   $query->where('h.fpodate', '<=', $request->date_to);

        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        if (!$request->has('all_po') && $request->has('only_pending')) {
            $query->whereRaw('d.fqty > COALESCE(ter.fqtyterima, 0)')->where('h.fclose', '0');
        }

        if ($request->sort_by == 'name') {
            $query->orderBy('h.fpodate', 'asc')->orderBy('h.fpono', 'asc');
        } else {
            $query->orderBy('h.fpono', 'asc');
        }

        return $query->get();
    }
}
