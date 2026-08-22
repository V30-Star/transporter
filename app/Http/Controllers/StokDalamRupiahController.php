<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class StokDalamRupiahController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get(['fcabangkode', 'fcabangname']);
        $warehouses = DB::table('mswh')->where('fnonactive', '0')->orderBy('fwhcode')->get(['fwhcode', 'fwhname', 'fbranchcode']);
        $groups = DB::table('ms_groupprd')->orderBy('fgroupcode')->get(['fgroupcode', 'fgroupname']);
        $mereks = DB::table('msmerek')->orderBy('fmerekcode')->get(['fmerekcode', 'fmerekname']);
        $products = DB::table('msprd')->where('ftype', 'Produk')->orderBy('fprdcode')->get(['fprdcode', 'fprdname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('stokdalamrupiah.index', compact(
            'branches',
            'warehouses',
            'groups',
            'mereks',
            'products',
            'isAuthorized',
            'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $this->validateInputs($request);
        $data = $this->getReportData($request);

        return view('stokdalamrupiah.print', [
            'groupedData' => $data['rows']->groupBy('fwhcode'),
            'grandTotal' => $data['grandTotal'],
            'request' => $request,
            'reportDate' => $request->input('report_date', date('Y-m-d')),
            'title' => 'Stok Dalam Rupiah',
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $this->validateInputs($request);
        $data = $this->getReportData($request);

        $filename = 'Stok_Dalam_Rupiah_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleSubtotal = new Style(fontBold: true, backgroundColor: 'FFF0F0');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Info
        $writer->addRow($makeRow([company_name()], $styleTitle));
        $writer->addRow($makeRow(['STOK DALAM RUPIAH'], $styleTitle));
        $writer->addRow($makeRow(['Kota:', company_setting()->fcity ?? '-']));
        $writer->addRow($makeRow(['Per Tanggal:', date('d/m/Y', strtotime($request->input('report_date', date('Y-m-d'))))]));
        $writer->addRow($makeRow(['Gudang:', $request->filled('warehouse') ? $request->warehouse : 'Semua Gudang']));
        $writer->addRow($makeRow(['Tanggal Cetak:', date('d/m/Y') . '  Jam: ' . date('H:i')]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'admin']));
        $writer->addRow($makeRow([]));

        // Table Columns
        $writer->addRow($makeRow([
            'No.', 'Kode Produk', 'Nama Produk', 'Satuan', 'Saldo Akhir', 'Harga Pokok', 'Total'
        ], $styleHeader));

        $grouped = $data['rows']->groupBy('fwhcode');
        foreach ($grouped as $whcode => $items) {
            $whName = $items->first()->fwhname ?? $whcode;
            $writer->addRow($makeRow([
                "GUDANG {$whName} ({$whcode})", '', '', '', '', '', ''
            ], $styleGroup));

            $subtotal = 0.0;
            $no = 1;
            foreach ($items as $row) {
                $subtotal += (float) $row->ftotal;
                $writer->addRow($makeRow([
                    $no++,
                    $row->fprdcode,
                    $row->fprdname,
                    $row->fsatuan,
                    (float) $row->fsaldo_akhir,
                    (float) $row->fhpp,
                    (float) $row->ftotal,
                ]));
            }

            $writer->addRow($makeRow([
                "Total GUDANG {$whName} ({$whcode})", '', '', '', '', '',
                (float) $subtotal
            ], $styleSubtotal));
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'Total Persediaan', '', '', '', '', '',
            (float) $data['grandTotal']
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function validateInputs(Request $request): void
    {
        $request->validate([
            'report_date' => ['required', 'date'],
            'warehouse' => ['nullable', 'string', 'max:50'],
            'group_code' => ['nullable', 'string', 'max:50'],
            'merek' => ['nullable', 'string', 'max:50'],
            'product_from' => ['nullable', 'string', 'max:50'],
            'product_to' => ['nullable', 'string', 'max:50'],
        ], [
            'report_date.required' => 'Tanggal laporan wajib diisi.',
            'report_date.date' => 'Format tanggal laporan tidak valid.',
        ]);
    }

    private function getReportData(Request $request): array
    {
        $reportDate = $request->input('report_date', date('Y-m-d'));
        $cutoff = $reportDate . ' 23:59:59';
        $reportYrMth = date('Ym', strtotime($reportDate));
        $cYrMth = $this->getEditPeriodYm();

        // Check Skenario A (prdacc history table) vs Skenario B (msprd.fhpp)
        $hasPrdaccHistory = Schema::hasTable('prdacc')
            && DB::table('prdacc')->where('fyrmth', $reportYrMth)->exists()
            && $reportYrMth < $cYrMth;

        // Subquery movement IN
        $inQuery = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->where('m.fstockmtdate', '<=', $cutoff)
            ->where(function ($q) {
                $q->where(fn($qq) => $qq->whereIn('m.fstockmtcode', ['BUY', 'TER', 'REJ', 'RUJ']))
                  ->orWhere(fn($qq) => $qq->whereIn('m.fstockmtcode', ['MUT', 'PRD']))
                  ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'CAB')->where('m.ftrancode', 'M'))
                  ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'ADJ')->where('m.ftrancode', 'M'));
            })
            ->selectRaw("
                CASE 
                    WHEN m.fstockmtcode IN ('MUT', 'PRD') OR (m.fstockmtcode = 'CAB' AND m.ftrancode = 'M') THEN m.fto
                    ELSE m.ffrom
                END as fwhcode,
                d.fprdcode,
                SUM(COALESCE(d.fqtykecil, d.fqty, 0)) as qty_in
            ")
            ->groupBy('fwhcode', 'd.fprdcode');

        // Subquery movement OUT
        $outQuery = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->where('m.fstockmtdate', '<=', $cutoff)
            ->where(function ($q) {
                $q->where(fn($qq) => $qq->whereIn('m.fstockmtcode', ['SRJ', 'PBR', 'REB', 'RUB', 'MUT']))
                  ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'CAB')->where('m.ftrancode', 'K'))
                  ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'ADJ')->where('m.ftrancode', 'K'));
            })
            ->selectRaw("m.ffrom as fwhcode, d.fprdcode, SUM(COALESCE(d.fqtykecil, d.fqty, 0)) as qty_out")
            ->groupBy('m.ffrom', 'd.fprdcode');

        $mainQuery = DB::table('prdwh as w')
            ->join('mswh as wh', 'w.fwhcode', '=', 'wh.fwhcode')
            ->join('msprd as p', 'w.fprdcode', '=', 'p.fprdcode')
            ->leftJoinSub($inQuery, 'ti', function ($join) {
                $join->on('w.fwhcode', '=', 'ti.fwhcode')->on('w.fprdcode', '=', 'ti.fprdcode');
            })
            ->leftJoinSub($outQuery, 'tout', function ($join) {
                $join->on('w.fwhcode', '=', 'tout.fwhcode')->on('w.fprdcode', '=', 'tout.fprdcode');
            })
            ->where('wh.fnonactive', '0')
            ->whereNotIn('p.fprdcode', ['AWAL', 'UM'])
            ->whereRaw('(COALESCE(w.fawal, 0) + COALESCE(ti.qty_in, 0) - COALESCE(tout.qty_out, 0)) <> 0');

        $this->applyBranchVisibilityScope($mainQuery, 'wh.fbranchcode');

        if ($request->filled('warehouse')) {
            $mainQuery->where('w.fwhcode', $request->input('warehouse'));
        }
        if ($request->filled('group_code')) {
            $mainQuery->where('p.fgroupcode', $request->input('group_code'));
        }
        if ($request->filled('merek')) {
            $mainQuery->where('p.fmerek', $request->input('merek'));
        }
        if ($request->filled('product_from')) {
            $mainQuery->where('p.fprdcode', '>=', $request->input('product_from'));
        }
        if ($request->filled('product_to')) {
            $mainQuery->where('p.fprdcode', '<=', $request->input('product_to'));
        }

        if ($hasPrdaccHistory) {
            $mainQuery->leftJoin('prdacc as a', function ($join) use ($reportYrMth) {
                $join->on('p.fprdcode', '=', 'a.fprdcode')->where('a.fyrmth', $reportYrMth);
            });
            $hppSelect = 'COALESCE(CASE WHEN a.fhppnet > 0 THEN a.fhppnet ELSE a.fhpp END, p.fhpp, 0) as fhppkecil';
        } else {
            $hppSelect = 'COALESCE(p.fhpp, 0) as fhppkecil';
        }

        $rawResults = $mainQuery->selectRaw("
            w.fwhcode,
            wh.fwhname,
            p.fprdcode,
            p.fprdname,
            COALESCE(NULLIF(TRIM(p.fsatuanbesar2), ''), NULLIF(TRIM(p.fsatuanbesar), ''), p.fsatuankecil) as fsatuan,
            COALESCE(NULLIF(p.fqtykecil, 0), 1) as fqtykecil,
            {$hppSelect},
            (COALESCE(w.fawal, 0) + COALESCE(ti.qty_in, 0) - COALESCE(tout.qty_out, 0)) as fsaldo_kecil
        ")
        ->orderBy('w.fwhcode')
        ->orderBy('p.fprdcode')
        ->get();

        $rows = collect();
        $grandTotal = 0.0;

        foreach ($rawResults as $r) {
            $ratio = (float) $r->fqtykecil > 0 ? (float) $r->fqtykecil : 1.0;
            $saldoAkhir = (float) $r->fsaldo_kecil / $ratio;
            $hppBesar = (float) $r->fhppkecil * $ratio;
            $total = $saldoAkhir * $hppBesar;

            $r->fsaldo_akhir = $saldoAkhir;
            $r->fhpp = $hppBesar;
            $r->ftotal = $total;

            $grandTotal += $total;
            $rows->push($r);
        }

        return [
            'rows' => $rows,
            'grandTotal' => $grandTotal,
        ];
    }
}
