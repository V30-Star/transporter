<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingKasController extends Controller
{
    public function pengeluaranKasIndex(Request $request)
    {
        return $this->renderIndex($request, 'BKK');
    }

    public function penerimaanKasIndex(Request $request)
    {
        return $this->renderIndex($request, 'BKM');
    }

    public function printPengeluaranKas(Request $request)
    {
        return $this->renderPrint($request, 'BKK');
    }

    public function printPenerimaanKas(Request $request)
    {
        return $this->renderPrint($request, 'BKM');
    }

    private function renderIndex(Request $request, string $tranCode)
    {
        $filterDateFrom = $request->query('filter_date_from') ?: Carbon::now('Asia/Bangkok')->startOfMonth()->format('Y-m-d');
        $filterDateTo = $request->query('filter_date_to') ?: Carbon::now('Asia/Bangkok')->format('Y-m-d');
        $filterAccount = (string) $request->query('filter_account', '');
        $onlyGiroMundur = $request->boolean('only_giro_mundur');

        $accounts = Account::query()
            ->where('fnonactive', '0')
            ->orderBy('faccount')
            ->get(['faccount', 'faccname']);

        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingkas.index', [
            'pageTitle' => $tranCode === 'BKK' ? 'Laporan Pengeluaran Kas/Bank' : 'Laporan Penerimaan Kas/Bank',
            'printRoute' => $tranCode === 'BKK'
                ? route('reportingkas.pengeluaran.print')
                : route('reportingkas.penerimaan.print'),
            'resetRoute' => $tranCode === 'BKK'
                ? route('reportingkas.pengeluaran.index')
                : route('reportingkas.penerimaan.index'),
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo' => $filterDateTo,
            'filterAccount' => $filterAccount,
            'onlyGiroMundur' => $onlyGiroMundur,
            'accounts' => $accounts,
            'branches' => $branches,
            'isAuthorized' => $isAuthorized,
            'userBranchCode' => $userBranchCode,
        ]);
    }

    private function renderPrint(Request $request, string $tranCode)
    {
        $data = $this->getReportData($request, $tranCode);
        $records = $data['records'];
        $detailsByHeader = $data['detailsByHeader'];

        $filterDateFrom = $request->query('filter_date_from');
        $filterDateTo = $request->query('filter_date_to');
        $filterAccount = trim((string) $request->query('filter_account', ''));
        $onlyGiroMundur = $request->boolean('only_giro_mundur');

        $accountName = null;
        if ($filterAccount !== '') {
            $accountName = Account::query()
                ->where('faccount', $filterAccount)
                ->value('faccname');
        }

        $grandTotal = (float) $records->sum(fn($row) => (float) ($row->famountpay ?? 0));

        return view('reportingkas.print', [
            'pageTitle' => $tranCode === 'BKK' ? 'Laporan Pengeluaran Kas/Bank' : 'Laporan Penerimaan Kas/Bank',
            'records' => $records,
            'detailsByHeader' => $detailsByHeader,
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo' => $filterDateTo,
            'filterAccount' => $filterAccount,
            'filterAccountName' => $accountName,
            'onlyGiroMundur' => $onlyGiroMundur,
            'grandTotal' => $grandTotal,
            'printDate' => Carbon::now()->format('d/m/Y H:i:s'),
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function getReportData(Request $request, string $tranCode)
    {
        $filterDateFrom = $request->query('filter_date_from');
        $filterDateTo = $request->query('filter_date_to');
        $filterAccount = trim((string) $request->query('filter_account', ''));
        $onlyGiroMundur = $request->boolean('only_giro_mundur');

        $query = DB::table('trkasmt')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'trkasmt.faccountheader')
            ->where('trkasmt.ftrancode', $tranCode)
            ->select([
                'trkasmt.*',
                'acc.faccname as header_account_name',
            ]);
        $this->applyBranchVisibilityScope($query, 'trkasmt.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (! empty($selectedBranches)) {
            $query->whereIn('trkasmt.fbranchcode', (array) $selectedBranches);
        }

        if (! empty($filterDateFrom)) {
            $query->whereDate('trkasmt.fkasmtdate', '>=', $filterDateFrom);
        }

        if (! empty($filterDateTo)) {
            $query->whereDate('trkasmt.fkasmtdate', '<=', $filterDateTo);
        }

        if ($filterAccount !== '') {
            $query->where('trkasmt.faccountheader', $filterAccount);
        }

        if ($onlyGiroMundur) {
            $query->where('trkasmt.fgiromundur', '1');
        }

        $records = $query
            ->orderBy('trkasmt.fkasmtdate')
            ->orderBy('trkasmt.fkasmtno')
            ->get();

        $headerIds = $records->pluck('fkasmtid')->filter()->all();

        $detailsByHeader = DB::table('trkasdt as dt')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'dt.faccount')
            ->leftJoin('mssubaccount as sub', 'sub.fsubaccountcode', '=', 'dt.fsubaccount')
            ->whereIn('dt.fkasmtid', $headerIds)
            ->orderBy('dt.fkasmtid')
            ->orderBy('dt.fnou')
            ->get([
                'dt.*',
                'acc.faccname as account_name',
                'sub.fsubaccountname as subaccount_name',
            ])
            ->groupBy('fkasmtid');

        return compact('records', 'detailsByHeader');
    }

    public function exportPengeluaranExcel(Request $request)
    {
        return $this->exportExcel($request, 'BKK');
    }

    public function exportPenerimaanExcel(Request $request)
    {
        return $this->exportExcel($request, 'BKM');
    }

    private function exportExcel(Request $request, string $tranCode)
    {
        $data = $this->getReportData($request, $tranCode);
        $records = $data['records'];
        $detailsByHeader = $data['detailsByHeader'];

        $title = $tranCode === 'BKK' ? 'LAPORAN PENGELUARAN KAS/BANK' : 'LAPORAN PENERIMAAN KAS/BANK';
        $filename = ($tranCode === 'BKK' ? 'Laporan_Pengeluaran_Kas_Bank_' : 'Laporan_Penerimaan_Kas_Bank_') . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleSubgroup = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow([$title], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->filter_date_from.' s/d '.$request->filter_date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No. Voucher', 'Tanggal', 'Cabang', 'Account Header', 'Nama Account Header', 'Total Bayar', 'Keterangan',
            'No.', 'Account Detail', 'Nama Account Detail', 'Sub Account', 'Nama Sub Account', 'Nilai Bayar', 'Uraian'
        ], $styleHeader));

        $grandTotal = 0;

        foreach ($records as $row) {
            $details = $detailsByHeader->get($row->fkasmtid) ?: collect();
            $grandTotal += (float) ($row->famountpay ?? 0);

            // Header data row
            $writer->addRow($makeRow([
                $row->fkasmtno,
                $row->fkasmtdate ? date('d/m/Y', strtotime($row->fkasmtdate)) : '',
                $row->fbranchcode,
                $row->faccountheader,
                $row->header_account_name,
                (float) $row->famountpay,
                $row->fket,
                '', '', '', '', '', '', ''
            ], $styleSubgroup));

            foreach ($details as $index => $d) {
                // Detail row
                $writer->addRow($makeRow([
                    '', '', '', '', '', '', '',
                    $index + 1,
                    $d->faccount,
                    $d->account_name,
                    $d->fsubaccount,
                    $d->subaccount_name,
                    (float) $d->fkasdtvalue,
                    $d->fnote
                ]));
            }
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL', '', '', '', '', (float) $grandTotal, '', '', '', '', '', '', '', ''
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
