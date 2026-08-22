<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class TrialBalanceController extends Controller
{
    public function index()
    {
        $currentYm = $this->getEditPeriodYm();
        $accounts = DB::table('account')
            ->where('fend', '1')
            ->where('fnonactive', '0')
            ->orderBy('faccount', 'asc')
            ->get(['faccount', 'faccname']);

        return view('trialbalance.index', compact('currentYm', 'accounts'));
    }

    public function print(Request $request)
    {
        $this->validateInputs($request);
        $data = $this->getReportData($request);

        return view('trialbalance.print', [
            'rows' => $data['rows'],
            'totalSaldoAwal' => $data['totalSaldoAwal'],
            'totalMutasiDebet' => $data['totalMutasiDebet'],
            'totalMutasiKredit' => $data['totalMutasiKredit'],
            'totalSaldoAkhir' => $data['totalSaldoAkhir'],
            'saldoLabaBerjalan' => $data['saldoLabaBerjalan'],
            'labaBerjalanCode' => $data['labaBerjalanCode'],
            'periodFrom' => $request->period_from,
            'periodTo' => $request->period_to,
            'periodFromText' => $this->formatPeriodIndonesian($request->period_from),
            'periodToText' => $this->formatPeriodIndonesian($request->period_to),
            'accountFrom' => $request->account_from,
            'accountTo' => $request->account_to,
            'accountFromObj' => $data['accountFromObj'],
            'accountToObj' => $data['accountToObj'],
            'title' => 'TRIAL BALANCE',
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $this->validateInputs($request);
        $data = $this->getReportData($request);

        $filename = 'Trial_Balance_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleData = new Style(fontBold: false);
        $styleTotal = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleFooter = new Style(fontBold: true, backgroundColor: 'FFF3CD');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Info
        $writer->addRow($makeRow([company_name()], $styleTitle));
        $writer->addRow($makeRow(['TRIAL BALANCE'], $styleTitle));
        $writer->addRow($makeRow(['Kota:', company_setting()->fcity ?? '-']));
        $writer->addRow($makeRow(['Periode:', $this->formatPeriodIndonesian($request->period_from) . ' s.d ' . $this->formatPeriodIndonesian($request->period_to)]));
        
        $accountText = 'Semua Account';
        if (filled($request->account_from) || filled($request->account_to)) {
            $fromLabel = $request->account_from ? ($request->account_from . ' - ' . ($data['accountFromObj']->faccname ?? '')) : 'Awal';
            $toLabel = $request->account_to ? ($request->account_to . ' - ' . ($data['accountToObj']->faccname ?? '')) : 'Akhir';
            $accountText = "{$fromLabel} s.d {$toLabel}";
        }
        $writer->addRow($makeRow(['Account:', $accountText]));
        $writer->addRow($makeRow(['Tanggal Cetak:', date('d/m/Y') . ' ' . date('H:i')]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'admin']));
        $writer->addRow($makeRow([]));

        // Table Columns
        $writer->addRow($makeRow([
            'Account', 'Nama Account', 'Saldo Awal', 'Mutasi Debet', 'Mutasi Kredit', 'Saldo Akhir'
        ], $styleHeader));

        foreach ($data['rows'] as $row) {
            $writer->addRow($makeRow([
                $row->faccount,
                $row->faccname,
                (float) $row->fsaldoawal,
                (float) $row->fmutasidebet,
                (float) $row->fmutasicredit,
                (float) $row->fsaldoakhir
            ], $styleData));
        }

        // Total Row
        $writer->addRow($makeRow([
            'TOTAL',
            '',
            (float) $data['totalSaldoAwal'],
            (float) $data['totalMutasiDebet'],
            (float) $data['totalMutasiKredit'],
            (float) $data['totalSaldoAkhir']
        ], $styleTotal));

        // Saldo Laba Ditahan / Laba Tahun Berjalan
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'Saldo Laba Tahun Berjalan',
            '',
            '',
            '',
            '',
            (float) $data['saldoLabaBerjalan']
        ], $styleFooter));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function validateInputs(Request $request): void
    {
        $request->validate([
            'period_from' => ['required', 'regex:/^\d{6}$/'],
            'period_to' => ['required', 'regex:/^\d{6}$/', 'gte:period_from'],
            'account_from' => ['nullable', 'string', 'max:50'],
            'account_to' => ['nullable', 'string', 'max:50'],
        ], [
            'period_from.required' => 'Periode Dari wajib diisi.',
            'period_from.regex' => 'Format Periode Dari harus YYYYMM (6 digit angka, contoh: 202601).',
            'period_to.required' => 'Periode Sampai wajib diisi.',
            'period_to.regex' => 'Format Periode Sampai harus YYYYMM (6 digit angka, contoh: 202601).',
            'period_to.gte' => 'Periode Sampai harus lebih besar atau sama dengan Periode Dari.',
        ]);
    }

    private function getReportData(Request $request): array
    {
        $periodFrom = $request->input('period_from');
        $periodTo = $request->input('period_to');
        $accountFrom = $request->input('account_from');
        $accountTo = $request->input('account_to');

        $labaBerjalanCode = trim((string) DB::table('set_account')
            ->where('faccount_name', 'LABATAHUNBERJALAN')
            ->value('faccount'));

        if ($labaBerjalanCode === '') {
            $labaBerjalanCode = '31040';
        }

        $bindings = [
            $periodFrom,
            $periodTo,
            $periodFrom,
            $periodTo,
            $labaBerjalanCode,
        ];

        $accountWhere = '';
        if (filled($accountFrom) && filled($accountTo)) {
            $accountWhere = ' AND t.faccount BETWEEN ? AND ? ';
            $bindings[] = $accountFrom;
            $bindings[] = $accountTo;
        } elseif (filled($accountFrom)) {
            $accountWhere = ' AND t.faccount >= ? ';
            $bindings[] = $accountFrom;
        } elseif (filled($accountTo)) {
            $accountWhere = ' AND t.faccount <= ? ';
            $bindings[] = $accountTo;
        }

        $sql = "
            SELECT 
                t.faccount,
                t.faccname,
                t.fnormal,
                COALESCE(CASE WHEN t.fnormal = 'D' THEN sa.fsaldoawal ELSE sa.fsaldoawal * -1 END, 0) AS fsaldoawal,
                COALESCE(sm.fmutasidebet, 0) AS fmutasidebet,
                COALESCE(sm.fmutasicredit, 0) AS fmutasicredit,
                COALESCE(CASE WHEN t.fnormal = 'D' THEN se.fsaldoakhir ELSE se.fsaldoakhir * -1 END, 0) AS fsaldoakhir
            FROM account t
            LEFT JOIN accountsaldo sa ON sa.faccount = t.faccount AND sa.fyrmth = ?
            LEFT JOIN accountsaldo se ON se.faccount = t.faccount AND se.fyrmth = ?
            LEFT JOIN (
                SELECT 
                    faccount, 
                    SUM(COALESCE(fmutasidebet, 0)) AS fmutasidebet, 
                    SUM(COALESCE(fmutasicredit, 0)) AS fmutasicredit
                FROM accountsaldo
                WHERE fyrmth >= ? AND fyrmth <= ?
                GROUP BY faccount
            ) sm ON sm.faccount = t.faccount
            WHERE t.fend = '1'
              AND t.faccount <> ?
              {$accountWhere}
              AND (
                  COALESCE(CASE WHEN t.fnormal = 'D' THEN sa.fsaldoawal ELSE sa.fsaldoawal * -1 END, 0) <> 0
                  OR COALESCE(sm.fmutasidebet, 0) <> 0
                  OR COALESCE(sm.fmutasicredit, 0) <> 0
                  OR COALESCE(CASE WHEN t.fnormal = 'D' THEN se.fsaldoakhir ELSE se.fsaldoakhir * -1 END, 0) <> 0
              )
            ORDER BY t.faccount ASC
        ";

        $rows = DB::select($sql, $bindings);

        // Laba Tahun Berjalan
        $labaSql = "
            SELECT COALESCE(SUM(fsaldo), 0) AS saldo_laba_berjalan
            FROM accountsaldo
            WHERE fyrmth >= ? AND fyrmth <= ?
              AND faccount = ?
        ";
        $labaRow = DB::selectOne($labaSql, [$periodFrom, $periodTo, $labaBerjalanCode]);
        $saldoLabaBerjalan = (float) ($labaRow->saldo_laba_berjalan ?? 0);

        $totalSaldoAwal = 0.0;
        $totalMutasiDebet = 0.0;
        $totalMutasiKredit = 0.0;
        $totalSaldoAkhir = 0.0;

        foreach ($rows as $row) {
            $totalSaldoAwal += (float) $row->fsaldoawal;
            $totalMutasiDebet += (float) $row->fmutasidebet;
            $totalMutasiKredit += (float) $row->fmutasicredit;
            $totalSaldoAkhir += (float) $row->fsaldoakhir;
        }

        $accountFromObj = filled($accountFrom) ? DB::table('account')->where('faccount', $accountFrom)->first() : null;
        $accountToObj = filled($accountTo) ? DB::table('account')->where('faccount', $accountTo)->first() : null;

        return [
            'rows' => $rows,
            'totalSaldoAwal' => $totalSaldoAwal,
            'totalMutasiDebet' => $totalMutasiDebet,
            'totalMutasiKredit' => $totalMutasiKredit,
            'totalSaldoAkhir' => $totalSaldoAkhir,
            'saldoLabaBerjalan' => $saldoLabaBerjalan,
            'labaBerjalanCode' => $labaBerjalanCode,
            'accountFromObj' => $accountFromObj,
            'accountToObj' => $accountToObj,
        ];
    }

    private function formatPeriodIndonesian(string $yrmth): string
    {
        if (strlen($yrmth) !== 6) {
            return $yrmth;
        }

        $year = substr($yrmth, 0, 4);
        $monthNum = (int) substr($yrmth, 4, 2);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthName = $months[$monthNum] ?? sprintf('%02d', $monthNum);

        return "{$monthName} {$year}";
    }
}
