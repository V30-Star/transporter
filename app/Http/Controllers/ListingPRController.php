<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingPRController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();

        return view('listingpr.index', compact('suppliers'));
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);

        $groupedData = $results->groupBy('fprno');
        $chunkedData = $groupedData->chunk(4);

        return view('listingpr.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'user_session' => auth()->user(),
            'request' => $request,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $results = $this->getRawData($request);
        $groupedData = $results->groupBy('fprno');

        $filename = 'Listing_PR_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        // --- Styles ---
        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleMaster = new Style(fontBold: true);
        $styleDetail = new Style(fontColor: 'CC0000');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );

            return new Row($cells);
        };

        // --- Header Informasi ---
        $writer->addRow($makeRow(['LISTING PURCHASE REQUEST'], $styleTitle));
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
            'No. PR',
            'Tanggal',
            'Nama Supplier',
            'Tgl. Dibutuhkan',
            'Tgl. Paling Lambat',
            'User-id',
            'Produk#',
            'Nama Produk',
            'Satuan',
            'Qty. PR',
            'Qty. PO',
        ], $styleHeader));

        $totalQtyPR = 0;
        $totalQtyPO = 0;

        foreach ($groupedData as $fprno => $details) {
            $h = $details->first();
            $isFirst = true;

            foreach ($details as $d) {
                $totalQtyPR += (float) $d->fqty;
                $totalQtyPO += (float) $d->fqtypo;

                $writer->addRow($makeRow([
                    $isFirst ? $h->fprno : '',
                    $isFirst ? Carbon::parse($h->fprdate)->format('d/m/Y') : '',
                    $isFirst ? ($h->fsuppliername ?? '') : '',
                    $isFirst ? ($h->fneeddate ? Carbon::parse($h->fneeddate)->format('d/m/Y') : '-') : '',
                    $isFirst ? ($h->fduedate ? Carbon::parse($h->fduedate)->format('d/m/Y') : '-') : '',
                    $isFirst ? trim($h->fusercreate) : '',
                    $d->fprdcode,
                    $d->fprdname,
                    $d->fsatuan,
                    (float) $d->fqty,
                    (float) $d->fqtypo,
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
            '',
            'TOTAL:',
            $totalQtyPR,
            $totalQtyPO,
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // --- Private helper agar query tidak duplikat ---
    private function getRawData(Request $request)
    {
        $subPO = DB::table('tr_pod')
            ->select('fprdid', 'frefdtno', 'fprdcode', 'frefdtid', DB::raw('sum(fqtykecil) as fqtypo'))
            ->groupBy('fprdid', 'frefdtno', 'fprdcode', 'frefdtid');

        $query = DB::table('tr_prh as h')
            ->leftJoin('tr_prd as d', 'h.fprno', '=', 'd.fprno')
            ->leftJoin('mssupplier as s', 'h.fsupplier', '=', 's.fsupplierid')
            ->leftJoin('msprd as p', 'd.fprdid', '=', 'p.fprdid')
            ->leftJoinSub($subPO, 'o', function ($join) {
                $join->on('o.frefdtid', '=', 'h.fprhid')
                    ->on('o.fprdid', '=', 'p.fprdid');
            })
            ->select(
                'h.fprhid',
                'h.fprno',
                'h.fprdate',
                'h.fneeddate',
                'h.fduedate',
                'h.fusercreate',
                's.fsuppliername',
                'd.fprdcode as prd_code_id',
                'p.fprdcode',
                'p.fprdname',
                'd.fqty',
                'd.fsatuan',
                DB::raw('COALESCE(o.fqtypo, 0) as fqtypo')
            );

        if ($request->date_from) {
            $query->where('h.fprdate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('h.fprdate', '<=', $request->date_to);
        }

        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        if (! $request->has('all_pr') && $request->has('only_pending')) {
            $query->whereRaw('(CASE WHEN d.fsatuan = p.fsatuanbesar THEN d.fqty * p.fqtykecil ELSE d.fqty END - COALESCE(o.fqtypo, 0)) > 0');
        }

        if ($request->sort_by == 'name') {
            $query->orderBy('h.fprdate', 'asc')->orderBy('h.fprno', 'asc');
        } else {
            $query->orderBy('h.fprno', 'asc');
        }

        return $query->get();
    }
}
