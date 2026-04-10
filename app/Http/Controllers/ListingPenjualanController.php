<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

class ListingPenjualanController extends Controller
{
    public function index()
    {
        $groups   = DB::table('ms_groupprd')->get();
        $mereks   = DB::table('msmerek')->get();
        $salesmans = DB::table('mssalesman')->get();

        return view('listingpenjualan.index', compact('groups', 'mereks', 'salesmans'));
    }

    // ─────────────────────────────────────────────────────────────
    //  QUERY BUILDER BERSAMA (dipakai oleh print & export)
    // ─────────────────────────────────────────────────────────────
    private function buildQuery(Request $request)
    {
        $query = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomerid')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->join('mssalesman as s', 'm.fsalesman', '=', 's.fsalesmanid')
            ->select(
                'm.fsono',
                'm.ftaxno',
                'm.fsodate',
                'm.fcustno',
                'm.famountsonet',
                'm.fongkosangkut',
                'c.fcustomername',
                'm.fdiscpersen',
                'm.fsalesman',
                'm.famountpajak',
                'm.famountso',
                'm.fket',
                'm.fuserid',
                'm.fincludeppn',
                'm.fppnpersen',
                'm.fdiscount',
                'd.fprdcode',
                'd.fqty',
                'd.fsalesnet as fprice',
                'd.fdisc',
                'd.fsatuan',
                'd.fdesc',
                'd.fnou',
                'd.fpricenet',
                'd.frefsrj as frefso',
                'p.fprdname',
                's.fsalesmanname',
                DB::raw('ROUND(m.ftotalsalesnet) as famountgross'),
                DB::raw('d.fsalesnet * d.fqty as famount')
            );
            
        if ($request->date_from)   $query->where('m.fsodate', '>=', $request->date_from);
        if ($request->date_to)     $query->where('m.fsodate', '<=', $request->date_to);
        if ($request->prd_from)    $query->where('d.fprdcode', '>=', $request->prd_from);
        if ($request->prd_to)      $query->where('d.fprdcode', '<=', $request->prd_to);
        if ($request->cust_from)   $query->where('m.fcustno', '>=', $request->cust_from);
        if ($request->cust_to)     $query->where('m.fcustno', '<=', $request->cust_to);
        if ($request->group_code)  $query->where('p.fgroupcode', $request->group_code);
        if ($request->merek_code)  $query->where('p.fmerek', $request->merek_code);
        if ($request->salesman)    $query->where('m.fsalesman', $request->salesman);
        if ($request->has('belum_kirim')) $query->where('d.fqtyremain', '>', 0);

        return $query->orderBy('m.fsono')->orderBy('d.fnou');
    }

    // ─────────────────────────────────────────────────────────────
    //  PRINT (HTML)
    // ─────────────────────────────────────────────────────────────
    public function print(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type    = $request->display_type;

        $groupedData = $results->groupBy('fsono');
        $perPage     = ($type == 'detail') ? 4 : 15;
        $chunkedData = $groupedData->chunk($perPage);

        return view('listingpenjualan.print', [
            'chunkedData'  => $chunkedData,
            'totalPages'   => $chunkedData->count(),
            'type'         => $type,
            'user_session' => auth()->user(),
            'request'      => $request,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  EXPORT EXCEL  (OpenSpout)
    // ─────────────────────────────────────────────────────────────
    public function exportExcel(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type    = $request->display_type ?? 'rekap';
        $grouped = $results->groupBy('fsono');

        $filename = 'listing_penjualan_' . date('Ymd_His') . '.xlsx';

        // ── Style preset (named constructor args, sama seperti ListingPenerimaan) ──────
        $styleTitle     = new Style(fontBold: true,  fontColor: 'C00000');
        $styleInfo      = new Style();
        $styleColHeader = new Style(fontBold: true,  backgroundColor: 'D3D3D3');
        $styleDetHeader = new Style(fontBold: true,  fontColor: 'C00000', backgroundColor: 'FFE6E6');
        $styleInvHeader = new Style(fontBold: true);
        $styleDetail    = new Style(fontColor: 'C00000');
        $styleFooter    = new Style(fontBold: true,  backgroundColor: '333333', fontColor: 'FFFFFF');

        // ── Buat writer ───────────────────────────────────────────
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer   = new Writer();
        $writer->openToFile($tempFile);

        $makeRow = function (array $values, ?Style $style = null) use ($writer): void {
            $cells = array_map(
                fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            $writer->addRow(new Row($cells));
        };

        // ── Baris judul & info ────────────────────────────────────
        $periodeFrom = $request->date_from ?? '...';
        $periodeTo   = $request->date_to   ?? '...';
        $customer    = $request->cust_from ? '[' . $request->cust_from . ']' : 'Semua';
        $operator    = auth()->user()->fname ?? 'admin';

        $makeRow(['LISTING PENJUALAN'], $styleTitle);
        $makeRow(["Periode: {$periodeFrom} s/d {$periodeTo}"], $styleInfo);
        $makeRow(["Customer: {$customer}", '', '', '', '', '', '', 'Tanggal: ' . date('d/m/Y'), '', 'Opr: ' . $operator], $styleInfo);
        $makeRow([]);   // baris kosong

        // ── Header kolom utama ────────────────────────────────────
        // Kolom 1-10: info faktur | Kolom 11-18: detail barang (menyamping)
        $headerRow = ['No.Faktur', 'No.Pajak', 'Tanggal', 'Customer', 'Salesman', 'Bruto', 'Netto', 'PPN', 'Ongkos', 'Total'];

        if ($type === 'detail') {
            $headerRow = array_merge($headerRow, [
                'Kode Barang',
                'Nama Barang',
                'No.Ref',
                'Qty.Kirim',
                'Qty.Jual',
                '@Harga',
                'Disc%',
                'Jumlah'
            ]);
        }

        $makeRow($headerRow, $styleColHeader);

        // ── Data ──────────────────────────────────────────────────
        foreach ($grouped as $fsono => $details) {
            $h = $details->first();

            $fakturCols = [
                $h->fsono,
                $h->ftaxno ?? '-',
                date('d/m/y', strtotime($h->fsodate)),
                $h->fcustomername,
                $h->fsalesmanname ?? '-',
                (float) $h->famountgross,
                (float) $h->famountsonet,
                (float) $h->famountpajak,
                (float) $h->fongkosangkut,
                (float) $h->famountso,
            ];

            if ($type === 'detail') {
                // Setiap item detail = 1 baris, info faktur diulang di kolom kiri
                $isFirst = true;
                foreach ($details as $d) {
                    $detailCols = [
                        $d->fprdcode,
                        $d->fprdname,
                        $d->frefso ?? '-',
                        (float) $d->fqty,
                        (float) $d->fprice,
                        $d->fdisc,
                        (float) $d->famount,
                    ];

                    // Baris pertama: tampilkan info faktur + detail
                    // Baris berikutnya: kolom faktur dikosongkan, detail tetap
                    $leftCols = $isFirst
                        ? $fakturCols
                        : array_fill(0, 10, '');

                    $makeRow(array_merge($leftCols, $detailCols), $isFirst ? $styleInvHeader : $styleDetail);
                    $isFirst = false;
                }
            } else {
                // Mode rekap: 1 baris per faktur tanpa detail
                $makeRow($fakturCols, $styleInvHeader);
            }

            // Baris pemisah kosong antar faktur
            $makeRow([]);
        }

        // ── Baris penutup ─────────────────────────────────────────
        $makeRow(['*** Akhir Laporan Penjualan ***'], $styleFooter);

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
