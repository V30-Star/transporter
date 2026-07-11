<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingPenjualanController extends Controller
{
    public function index()
    {
        $groups = DB::table('ms_groupprd')->get();
        $mereks = DB::table('msmerek')->get();
        $salesmans = DB::table('mssalesman')->get();
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingpenjualan.index', compact('groups', 'mereks', 'salesmans', 'branches', 'isAuthorized', 'userBranchCode'));
    }

    // ─────────────────────────────────────────────────────────────
    //  QUERY BUILDER BERSAMA (dipakai oleh print & export)
    // ─────────────────────────────────────────────────────────────
    private function buildQuery(Request $request)
    {
        $query = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->join('mssalesman as s', 'm.fsalesman', '=', 's.fsalesmancode')
            ->select(
                'm.fbranchcode',
                'm.fsono',
                'm.ftrcode',
                'm.ftaxno',
                'm.fsodate',
                'm.fcustno',
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
                DB::raw("
                    CASE 
                        WHEN m.fincludeppn = '1' THEN (100 / (100 + m.fppnpersen)) * m.fdiscount 
                        ELSE m.fdiscount 
                    END as fdiscount
                "),
                DB::raw("
                    CASE 
                        WHEN m.fincludeppn = '1' THEN (100 / (100 + m.fppnpersen)) * m.famountsonet 
                        ELSE m.famountsonet 
                    END as famountsonet
                "),
                'd.fprdcode',
                'd.fqty',
                'd.fqtyremain',
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
        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (!empty($selectedBranches)) {
            $query->whereIn('m.fbranchcode', (array) $selectedBranches);
        }

        if ($request->date_from) {
            $query->where('m.fsodate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fsodate', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->prd_from) {
            $query->where('d.fprdcode', '>=', $request->prd_from);
        }
        if ($request->prd_to) {
            $query->where('d.fprdcode', '<=', $request->prd_to);
        }
        if ($request->cust_from) {
            $query->where('c.fcustomercode', '>=', $request->cust_from);
        }
        if ($request->cust_to) {
            $query->where('c.fcustomercode', '<=', $request->cust_to);
        }
        if ($request->group_code) {
            $query->where('p.fgroupcode', $request->group_code);
        }
        if ($request->merek_code) {
            $query->where('p.fmerek', $request->merek_code);
        }
        if ($request->salesman) {
            $query->where('m.fsalesman', $request->salesman);
        }
        if ($request->filled('ftypesales')) {
            $query->where('m.ftypesales', $request->ftypesales);
        }
        if ($request->has('belum_kirim')) {
            $query->where('d.fqtyremain', '>', 0);
        }
        $query->whereIn('m.ftrcode', $request->boolean('include_retur_penjualan') ? ['INV', 'REJ'] : ['INV']);

        return $query
            ->orderByRaw("CASE WHEN m.ftrcode = 'REJ' THEN 1 ELSE 0 END")
            ->orderBy('m.fsono')
            ->orderBy('d.fnou');
    }

    // ─────────────────────────────────────────────────────────────
    //  PRINT (HTML)
    // ─────────────────────────────────────────────────────────────
    public function print(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type = $request->display_type;

        $groupedData = $results->groupBy('fsono');

        return view('listingpenjualan.print', [
            'groupedData' => $groupedData,
            'type' => $type,
            'user_session' => auth()->user(),
            'request' => $request,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  EXPORT EXCEL  (OpenSpout)
    // ─────────────────────────────────────────────────────────────
    public function exportExcel(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type = $request->display_type ?? 'rekap';
        $grouped = $results->groupBy('fsono');

        $filename = 'listing_penjualan_' . date('Ymd_His') . '.xlsx';

        // ── Style preset (named constructor args, sama seperti ListingPenerimaan) ──────
        $styleTitle = new Style(fontBold: true, fontColor: 'C00000');
        $styleInfo = new Style;
        $styleColHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleDetHeader = new Style(fontBold: true, fontColor: 'C00000', backgroundColor: 'FFE6E6');
        $styleInvHeader = new Style(fontBold: true);
        $styleReturInvoice = new Style(fontBold: true, fontColor: 'C00000');
        $styleDetail = new Style(fontColor: 'C00000');
        $styleFooter = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        // ── Buat writer ───────────────────────────────────────────
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Writer;
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
        $periodeTo = $request->date_to ?? '...';
        $customer = $request->cust_from ? '[' . $request->cust_from . ']' : 'Semua';
        $operator = auth()->user()->fname ?? 'admin';

        $selectedBranchesStr = !empty($request->branch_codes)
            ? implode(', ', (array) $request->branch_codes)
            : 'Semua';
        $salesType = match ((string) $request->input('ftypesales', '')) {
            '1' => 'Uang Muka (UM)',
            '0' => 'Penjualan',
            default => 'Semua',
        };

        $makeRow(['LISTING PENJUALAN'], $styleTitle);
        $makeRow(["Periode: {$periodeFrom} s/d {$periodeTo}"], $styleInfo);
        $makeRow(["Cabang: {$selectedBranchesStr}"], $styleInfo);
        $makeRow(["Tipe Penjualan: {$salesType}"], $styleInfo);
        $makeRow(["Customer: {$customer}", '', '', '', '', '', '', 'Tanggal: ' . date('d/m/Y'), '', 'Opr: ' . $operator], $styleInfo);
        $makeRow([]);   // baris kosong

        // ── Header kolom utama ────────────────────────────────────
        // Kolom 1-10: info faktur | Kolom 11-18: detail barang (menyamping)
        $headerRow = ['No.Faktur', 'No.Pajak', 'Tanggal', 'Customer', 'Salesman', 'Disc', 'Netto', 'PPN', 'Ongkos', 'Total'];

        if ($type === 'detail') {
            $headerRow = array_merge($headerRow, [
                'Kode Barang',
                'Nama Barang',
                'No.Ref',
                'Qty.Kirim',
                'Qty.Jual',
                '@Harga',
                'Disc%',
                'Jumlah',
            ]);
        }

        $makeRow($headerRow, $styleColHeader);

        // ── Data ──────────────────────────────────────────────────
        foreach ($grouped as $fsono => $details) {
            $h = $details->first();
            $sign = $h->ftrcode === 'REJ' ? -1 : 1;
            $invoiceStyle = $h->ftrcode === 'REJ' ? $styleReturInvoice : $styleInvHeader;

            $fakturCols = [
                $h->fsono,
                $h->ftaxno ?? '-',
                date('d/m/y', strtotime($h->fsodate)),
                $h->fcustomername,
                $h->fsalesmanname ?? '-',
                abs((float) $h->fdiscount) * $sign,
                abs((float) $h->famountsonet) * $sign,
                abs((float) $h->famountpajak) * $sign,
                abs((float) $h->fongkosangkut) * $sign,
                abs((float) $h->famountso) * $sign,
            ];

            if ($type === 'detail') {
                // Setiap item detail = 1 baris, info faktur diulang di kolom kiri
                $isFirst = true;
                foreach ($details as $d) {
                    $detailCols = [
                        $d->fprdcode,
                        $d->fprdname,
                        $d->frefso ?? '-',
                        (float) ($d->fqtydeliver ?? 0),
                        (float) $d->fqty,
                        (float) $d->fprice,
                        $d->fdisc,
                        abs((float) $d->famount) * $sign,
                    ];

                    // Baris pertama: tampilkan info faktur + detail
                    // Baris berikutnya: kolom faktur dikosongkan, detail tetap
                    $leftCols = $isFirst
                        ? $fakturCols
                        : array_fill(0, 10, '');

                    $makeRow(array_merge($leftCols, $detailCols), $isFirst ? $invoiceStyle : $styleDetail);
                    $isFirst = false;
                }
            } else {
                // Mode rekap: 1 baris per faktur tanpa detail
                $makeRow($fakturCols, $invoiceStyle);
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
