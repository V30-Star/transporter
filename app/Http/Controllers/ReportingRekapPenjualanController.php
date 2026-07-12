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
        $groupCodeExpr = $groupBy === 'group' ? 'p.fgroupcode' : 'p.fmerek';
        $groupNameExpr = $groupBy === 'group' ? 'CAST(MIN(g.fgroupname) AS VARCHAR(50))' : 'CAST(MIN(merek.fmerekname) AS VARCHAR(50))';
        $qtyExpr = config('app.laporan_sales_satuan_besar', env('LaporanSalesSatuanBesar', '0')) === '1'
            ? 'SUM(CAST(d.fqtykecil AS NUMERIC) / NULLIF(CAST(p.fqtykecil AS NUMERIC), 0))'
            : 'SUM(d.fqtykecil)';
        $unitExpr = config('app.laporan_sales_satuan_besar', env('LaporanSalesSatuanBesar', '0')) === '1'
            ? 'CAST(MIN(p.fsatuanbesar) AS VARCHAR(10))'
            : 'CAST(MIN(p.fsatuankecil) AS VARCHAR(10))';

        $query = DB::table('tranmt as m')
            ->leftJoin('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('ms_groupprd as g', 'g.fgroupcode', '=', 'p.fgroupcode')
            ->leftJoin('msmerek as merek', 'p.fmerek', '=', 'merek.fmerekcode')
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
            ->groupByRaw("m.ftrcode, {$groupCodeExpr}, d.fprdcode, p.fprdname")
            ->orderByRaw("CASE WHEN m.ftrcode = 'REJ' THEN 1 ELSE 0 END")
            ->orderBy('fmerek')
            ->orderBy('d.fprdcode')
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
        if ($request->filled('prd_from')) {
            $query->where("{$d}.fprdcode", '>=', $request->input('prd_from'));
        }
        if ($request->filled('prd_to')) {
            $query->where("{$d}.fprdcode", '<=', $request->input('prd_to'));
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

        foreach ($groupedData as $source => $sourceRows) {
            $isReturn = $source === 'REJ';

            $writer->addRow($makeRow([
                $isReturn ? 'RETUR PENJUALAN' : 'PENJUALAN', '', '', '', '', ''
            ], $isReturn ? $styleReturn : $styleGroup));

            foreach ($sourceRows->groupBy('fmerek') as $groupCode => $items) {
                $groupName = $items->first()->fgroupname ?: $groupCode;
                $groupTotal = $items->sum(fn ($item) => $isReturn ? abs((float) $item->famount) * -1 : abs((float) $item->famount));
                $grandTotal += $groupTotal;

                // Group Row
                $writer->addRow($makeRow([
                    'Group: '.$groupCode.' - '.$groupName, '', '', '', '', ''
                ], $isReturn ? $styleReturn : $styleGroup));

                foreach ($items as $index => $row) {
                    $writer->addRow($makeRow([
                        $index + 1,
                        $row->fprdcode,
                        $row->fprdname,
                        (float) $row->fqty,
                        $row->fsatuan,
                        $isReturn ? abs((float) $row->famount) * -1 : abs((float) $row->famount)
                    ], $isReturn ? $styleReturn : null));
                }

                // Subtotal Row
                $writer->addRow($makeRow([
                    'Subtotal '.$groupName, '', '', '', '',
                    (float) $groupTotal
                ], $isReturn ? $styleReturnSubtotal : $styleSubtotal));
            }
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
