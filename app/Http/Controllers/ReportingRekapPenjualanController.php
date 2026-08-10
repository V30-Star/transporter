<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingRekapPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $salesmans = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $groups = DB::table('ms_groupprd')->orderBy('fgroupcode')->get();
        $mereks = DB::table('msmerek')->orderBy('fmerekcode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get(['fprdcode', 'fprdname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingrekappenjualan.index', compact('branches', 'salesmans', 'groups', 'mereks', 'products', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $groupBy = $request->input('group_by') === 'group' ? 'group' : 'merek';
        $rows = $this->buildRows($request, $groupBy);

        return view('reportingrekappenjualan.print', [
            'rows' => $rows,
            'request' => $request,
            'groupBy' => $groupBy,
            'title' => 'Laporan Rekap Penjualan',
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function buildRows(Request $request, string $groupBy)
    {
        $groupCodeExpr = $groupBy === 'group' ? 'TRIM(p.fgroupcode)' : 'TRIM(p.fmerek)';
        $groupNameExpr = $groupBy === 'group' ? 'CAST(MIN(g.fgroupname) AS VARCHAR(50))' : 'CAST(MIN(merek.fmerekname) AS VARCHAR(50))';
        $qtyExpr = "SUM(
            CASE 
                WHEN COALESCE(NULLIF(TRIM(p.fsatuandefaultlaporan), ''), '1') = '2' THEN CAST(d.fqtykecil AS NUMERIC) / NULLIF(CAST(p.fqtykecil AS NUMERIC), 0)
                WHEN COALESCE(NULLIF(TRIM(p.fsatuandefaultlaporan), ''), '1') = '3' THEN CAST(d.fqtykecil AS NUMERIC) / NULLIF(CAST(p.fqtykecil2 AS NUMERIC), 0)
                ELSE CAST(d.fqtykecil AS NUMERIC)
            END
        )";
        $unitExpr = "CAST(
            MIN(
                CASE 
                    WHEN COALESCE(NULLIF(TRIM(p.fsatuandefaultlaporan), ''), '1') = '2' THEN p.fsatuanbesar
                    WHEN COALESCE(NULLIF(TRIM(p.fsatuandefaultlaporan), ''), '1') = '3' THEN p.fsatuanbesar2
                    ELSE p.fsatuankecil
                END
            ) AS VARCHAR(20)
        )";

        $query = DB::table('tranmt as m')
            ->leftJoin('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('ms_groupprd as g', DB::raw('TRIM(g.fgroupcode)'), '=', DB::raw('TRIM(p.fgroupcode)'))
            ->leftJoin('msmerek as merek', DB::raw('TRIM(merek.fmerekcode)'), '=', DB::raw('TRIM(p.fmerek)'))
            ->selectRaw("m.ftrcode AS fsource, {$groupCodeExpr} AS fmerek, {$groupNameExpr} AS fgroupname, {$qtyExpr} AS fqty, {$unitExpr} AS fsatuan, 
            SUM(CASE WHEN m.ftrcode = 'INV' THEN ABS((d.fsalesnet * d.fqty) - ((d.fsalesnet * d.fqty) * (COALESCE(CAST(NULLIF(d.fdisc, '') AS NUMERIC), 0) / 100))) WHEN m.ftrcode = 'REJ' THEN ABS(d.fprice * d.fqty) * -1 ELSE 0 END) AS famount,
             d.fprdcode, p.fprdname")
            ->whereIn('m.ftrcode', $request->boolean('include_retur_penjualan') ? ['INV', 'REJ'] : ['INV'])
            ->where('m.ftypesales', 0)
            ->whereNotIn('d.fprdcode', ['UM', 'AWAL'])
            ->where('m.fsodate', '>=', $request->input('date_from', now()->startOfMonth()->toDateString()))
            ->where('m.fsodate', '<=', $request->input('date_to', now()->toDateString()) . ' 23:59:59');

        $this->applyCommonFilters($query, $request, 'm', 'd', 'p');

        return $query
            ->groupByRaw("m.ftrcode, {$groupCodeExpr}, d.fprdcode, p.fprdname, p.fsatuandefaultlaporan, p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2, p.fqtykecil, p.fqtykecil2")
            ->orderBy('fmerek')
            ->orderBy('d.fprdcode')
            ->orderByRaw("CASE WHEN m.ftrcode = 'REJ' THEN 1 ELSE 0 END")
            ->get();
    }

    private function applyCommonFilters($query, Request $request, string $m, string $d, string $p, bool $withSalesman = true): void
    {
        $this->applyBranchVisibilityScope($query, "{$m}.fbranchcode");

        if ($request->filled('branch_codes')) {
            $query->whereIn("{$m}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($withSalesman && $request->filled('salesman')) {
            $query->where("{$m}.fsalesman", $request->input('salesman'));
        }
        if ($request->filled('group_code')) {
            $query->whereRaw("TRIM({$p}.fgroupcode) = ?", [$request->input('group_code')]);
        }
        if ($request->filled('merek_code')) {
            $query->whereRaw("TRIM({$p}.fmerek) = ?", [$request->input('merek_code')]);
        }
        if ($request->filled('selected_products')) {
            $selectedProducts = array_filter(array_map('trim', explode(',', (string) $request->input('selected_products'))));
            if (!empty($selectedProducts)) {
                $query->whereIn("{$d}.fprdcode", $selectedProducts);
            }
        } elseif ($request->filled('prd_from') || $request->filled('prd_to')) {
            if ($request->filled('prd_from')) {
                $query->where("{$d}.fprdcode", '>=', $request->input('prd_from'));
            }
            if ($request->filled('prd_to')) {
                $query->where("{$d}.fprdcode", '<=', $request->input('prd_to'));
            }
        }
    }

    public function exportExcel(Request $request)
    {
        $groupBy = $request->input('group_by') === 'group' ? 'group' : 'merek';
        $rows = $this->buildRows($request, $groupBy);
        $groupedData = $rows->groupBy('fsource');

        $filename = 'Laporan_Rekap_Penjualan_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup = new Style(fontBold: true, backgroundColor: 'FFE6E6');
        $styleSubtotal = new Style(fontBold: true, backgroundColor: 'FFF0F0');
        $styleReturn = new Style(fontBold: true, fontColor: 'CC0000');
        $styleReturnSubtotal = new Style(fontBold: true, backgroundColor: 'FFF0F0', fontColor: 'CC0000');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['LAPORAN REKAP PENJUALAN'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Grouping:', $groupBy === 'group' ? 'By Group Produk' : 'By Merek']));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No.', 'Kode Barang', 'Nama Barang', 'Quantity', 'Satuan', 'Total Penjualan'
        ], $styleHeader));

        $grandTotal = 0;

        foreach ($rows->groupBy('fmerek') as $groupCode => $items) {
            $groupName = $items->first()->fgroupname ?: $groupCode;
            $groupTotal = $items->sum(fn ($item) => $item->fsource === 'REJ' ? -abs((float) $item->famount) : abs((float) $item->famount));
            $grandTotal += $groupTotal;

            // Group Row
            $writer->addRow($makeRow([
                'Group: '.$groupCode.' - '.$groupName, '', '', '', '', ''
            ], $styleGroup));

            foreach ($items as $index => $row) {
                $isReturn = $row->fsource === 'REJ';
                $rowQty = $isReturn ? -abs((float) $row->fqty) : abs((float) $row->fqty);
                $rowAmount = $isReturn ? -abs((float) $row->famount) : abs((float) $row->famount);

                $writer->addRow($makeRow([
                    $index + 1,
                    $row->fprdcode,
                    $row->fprdname,
                    (float) $rowQty,
                    $row->fsatuan,
                    (float) $rowAmount
                ], $isReturn ? $styleReturn : null));
            }

            // Subtotal Row
            $writer->addRow($makeRow([
                'Subtotal '.$groupName, '', '', '', '',
                (float) $groupTotal
            ], $styleSubtotal));
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL', '', '', '', '',
            (float) $grandTotal
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
