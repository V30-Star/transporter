<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingPenjualanDpController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingpenjualandp.index', compact('branches', 'customers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        [$masters, $details, $filters] = $this->data($request);

        return view('reportingpenjualandp.print', [
            'masters' => $masters,
            'detailsByDp' => $details->groupBy(fn ($row) => trim((string) $row->frefsrj)),
            'filters' => $filters,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$masters, $details, $filters] = $this->data($request);
        $detailsByDp = $details->groupBy(fn ($row) => trim((string) $row->frefsrj));
        $filename = 'Laporan_Penjualan_DP_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Writer;
        $writer->openToFile($tempFile);

        $bold = new Style(fontBold: true);
        $header = new Style(fontBold: true, backgroundColor: 'D9EAF7');
        $detailStyle = new Style(backgroundColor: 'F9FAFB');
        $grand = new Style(fontBold: true, backgroundColor: '111827', fontColor: 'FFFFFF');
        $makeRow = fn (array $values, ?Style $style = null) => new Row(array_map(fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value), $values));

        $writer->addRow($makeRow(['PT. UTALIYA'], $bold));
        $writer->addRow($makeRow(['Laporan Penjualan DP'], $bold));
        $writer->addRow($makeRow(['Periode', $filters['date_from'].' s/d '.$filters['date_to']]));
        $writer->addRow($makeRow(['Cabang', $filters['branch_label']]));
        $writer->addRow($makeRow(['Customer', $filters['customer_label']]));
        $writer->addRow($makeRow(['Sisa DP', $filters['sisa_dp_label']]));
        $writer->addRow($makeRow(['Tipe', $filters['report_type']]));
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow(['Cabang', 'Faktur DP', 'Tanggal', 'Customer', 'Nilai DP', 'Potong DP', 'Sisa DP'], $header));

        foreach ($masters as $master) {
            $writer->addRow($makeRow([
                $master->fbranchcode,
                $master->fsono,
                $master->fsodate,
                $master->fcustname ? $master->fcustname . ' (' . $master->fcustno . ')' : $master->fcustno,
                (float) $master->famountsonet,
                (float) $master->ftotaldp,
                (float) $master->fsisadp,
            ]));

            if ($filters['report_type'] === 'DETAIL') {
                foreach ($detailsByDp->get(trim((string) $master->fsono), collect()) as $detail) {
                    $isPemakaian = ($detail->tipe_label ?? 'Pemakaian DP') === 'Pemakaian DP';
                    $val = $isPemakaian ? -abs((float) $detail->famount) : abs((float) $detail->famount);
                    $writer->addRow($makeRow(['', $detail->tipe_label ?? 'Pemakaian DP', $detail->fsodate, $detail->fsono, '', $val, ''], $detailStyle));
                }
            }
        }

        $writer->addRow($makeRow(['GRAND TOTAL', '', '', '', (float) $masters->sum('famountsonet'), (float) $masters->sum('ftotaldp'), (float) $masters->sum('fsisadp')], $grand));
        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function data(Request $request): array
    {
        $filters = $this->filters($request);

        $dpUsed = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->selectRaw('d.frefsrj, SUM(COALESCE(d.fsalesnet * d.fqty, d.famount)) AS ftotaldp')
            ->where('d.fprdcode', 'UM')
            ->where('m.ftypesales', '<>', '1')
            ->groupBy('d.frefsrj');

        $dpRej = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->selectRaw('d.frefdtno, SUM(d.ftotprice) AS ftotaldprej')
            ->where('d.fprdcode', 'UM')
            ->where('m.fstockmtcode', 'REJ')
            ->groupBy('d.frefdtno');

        $masters = DB::table('tranmt as m')
            ->leftJoin('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->leftJoinSub($dpUsed, 'dp', 'm.fsono', '=', 'dp.frefsrj')
            ->leftJoinSub($dpRej, 'rej', 'm.fsono', '=', 'rej.frefdtno')
            ->select('m.ftranmtid', 'm.fbranchcode', 'm.fsono', 'm.fsodate', 'm.fcustno')
            ->selectRaw('c.fcustomername AS fcustname, m.famountsonet')
            ->selectRaw('COALESCE(ABS(dp.ftotaldp), 0) + COALESCE(rej.ftotaldprej, 0) AS ftotaldp')
            ->selectRaw('ROUND(m.famountsonet + COALESCE(dp.ftotaldp, 0) - COALESCE(rej.ftotaldprej, 0)) AS fsisadp')
            ->where('m.ftypesales', '1')
            ->where('m.fsodate', '>=', $filters['date_from'])
            ->where('m.fsodate', '<=', $filters['date_to'].' 23:59:59');

        $this->applyBranchVisibilityScope($masters, 'm.fbranchcode');
        if ($filters['branch_codes']) {
            $masters->whereIn('m.fbranchcode', $filters['branch_codes']);
        }
        if ($filters['customer_from'] !== '') {
            $masters->where('m.fcustno', '>=', $filters['customer_from']);
        }
        if ($filters['customer_to'] !== '') {
            $masters->where('m.fcustno', '<=', $filters['customer_to']);
        }
        if ($filters['sisa_dp_filter'] === 'SISA_DP_GT_0') {
            $masters->whereRaw('m.famountsonet + COALESCE(dp.ftotaldp, 0) - COALESCE(rej.ftotaldprej, 0) > 0');
        }

        $masterRows = $masters->orderBy('m.fcustno')->orderBy('m.fsodate')->orderBy('m.fsono')->orderBy('m.fbranchcode')->get();
        $refs = $masterRows->pluck('fsono')->map(fn ($value) => trim((string) $value))->filter()->values();
        $detailRows = collect();
        if ($refs->isNotEmpty()) {
            $usedDetails = DB::table('tranmt as m')
                ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
                ->selectRaw("d.frefsrj, m.fsono, m.fsodate, m.fcustno, d.famount, 'Pemakaian DP' as tipe_label")
                ->where('d.fprdcode', 'UM')
                ->where('m.ftypesales', '<>', '1')
                ->whereIn('d.frefsrj', $refs->all());

            $rejDetails = DB::table('trstockmt as m')
                ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
                ->selectRaw("d.frefdtno as frefsrj, m.fstockmtno as fsono, m.fstockmtdate as fsodate, COALESCE(m.fsupplier, '') as fcustno, (d.ftotprice * -1) as famount, 'Retur Penjualan DP' as tipe_label")
                ->where('d.fprdcode', 'UM')
                ->where('m.fstockmtcode', 'REJ')
                ->whereIn('d.frefdtno', $refs->all());

            $detailRows = $usedDetails->unionAll($rejDetails)
                ->orderBy('frefsrj')
                ->orderBy('fsodate')
                ->orderBy('fsono')
                ->get();
        }

        return [$masterRows, $detailRows, $filters];
    }

    private function filters(Request $request): array
    {
        $branches = array_values(array_filter((array) $request->input('branch_codes', [])));
        $customerFrom = trim((string) $request->input('customer_from', ''));
        $customerTo = trim((string) $request->input('customer_to', ''));
        $sisaDp = $request->input('sisa_dp_filter') === 'SISA_DP_GT_0' ? 'SISA_DP_GT_0' : 'SEMUA_FAKTUR';
        $reportType = $request->input('report_type') === 'REKAP' ? 'REKAP' : 'DETAIL';

        return [
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'branch_codes' => $branches,
            'customer_from' => $customerFrom,
            'customer_to' => $customerTo,
            'sisa_dp_filter' => $sisaDp,
            'report_type' => $reportType,
            'branch_label' => $branches ? implode(', ', $branches) : 'Semua Cabang',
            'customer_label' => $customerFrom || $customerTo ? $customerFrom.' s/d '.$customerTo : 'Semua Customer',
            'sisa_dp_label' => $sisaDp === 'SISA_DP_GT_0' ? 'Sisa DP > 0' : 'Semua Faktur',
        ];
    }
}
