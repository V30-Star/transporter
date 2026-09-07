<?php

namespace App\Http\Controllers;

use App\Models\LogUser;
use App\Models\Sysuser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class LogUserController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $userId = $request->input('user');
        $status = $request->input('status', 'all');

        $users = Sysuser::select('fsysuserid', 'fname')
            ->orderBy('fname')
            ->get();

        $query = $this->buildQuery($request);
        $logs = $query->get();

        // Calculate statistics
        $statsQuery = LogUser::query();
        if ($dateFrom) {
            $statsQuery->whereDate('login_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $statsQuery->whereDate('login_date', '<=', $dateTo);
        }
        if ($userId) {
            $statsQuery->where('akun', $userId);
        }

        $totalLogin = (clone $statsQuery)->count();
        $onlineNow = (clone $statsQuery)->whereNull('log_out_date')->count();
        $uniqueUsers = (clone $statsQuery)->distinct('akun')->count('akun');
        $todayLogin = (clone $statsQuery)->whereDate('login_date', now()->toDateString())->count();

        return view('loguser.index', compact(
            'logs',
            'users',
            'dateFrom',
            'dateTo',
            'userId',
            'status',
            'totalLogin',
            'onlineNow',
            'uniqueUsers',
            'todayLogin'
        ));
    }

    public function exportExcel(Request $request)
    {
        $logs = $this->buildQuery($request)->get();
        $filename = 'Log_User_Login_Logout_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleRow = new Style(fontBold: false);
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['DASHBOARD LOG USER LOGIN / LOGOUT REPORT'], $styleTitle));
        $writer->addRow($makeRow(['Periode:', ($request->date_from ?: 'Semua') . ' s/d ' . ($request->date_to ?: 'Semua')]));
        $writer->addRow($makeRow(['Tanggal Cetak:', date('d/m/Y') . ' Jam: ' . date('H:i')]));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No',
            'User ID (Akun)',
            'Nama Lengkap',
            'IP Address',
            'Komputer',
            'Tanggal & Waktu Login',
            'Tanggal & Waktu Logout',
            'Durasi Online',
            'Status',
        ], $styleHeader));

        $no = 1;
        foreach ($logs as $log) {
            $loginAt = $log->login_date ? Carbon::parse($log->login_date) : null;
            $logoutAt = $log->log_out_date ? Carbon::parse($log->log_out_date) : null;

            $durationText = '-';
            if ($loginAt && $logoutAt) {
                $durationText = $loginAt->diffForHumans($logoutAt, ['syntax' => Carbon::DIFF_ABSOLUTE, 'parts' => 2]);
            } elseif ($loginAt && !$logoutAt) {
                $durationText = $loginAt->diffForHumans(now(), ['syntax' => Carbon::DIFF_ABSOLUTE, 'parts' => 2]) . ' (Sedang Online)';
            }

            $statusText = $log->log_out_date ? 'Logout' : 'Online (Aktif)';

            $writer->addRow($makeRow([
                $no++,
                (string) $log->akun,
                (string) ($log->fname ?? '-'),
                (string) ($log->ip ?? '-'),
                (string) ($log->komp ?? '-'),
                $loginAt ? $loginAt->format('d/m/Y H:i:s') : '-',
                $logoutAt ? $logoutAt->format('d/m/Y H:i:s') : '-',
                $durationText,
                $statusText,
            ], $styleRow));
        }

        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'Total Record: ' . ($no - 1),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ], $styleGrandTotal));
        $writer->addRow($makeRow(['*** End of Report ***'], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        $logs = $this->buildQuery($request)->get();

        return view('loguser.print', [
            'logs' => $logs,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
            'userId' => $request->user,
            'status' => $request->status,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function buildQuery(Request $request)
    {
        $query = DB::table('log_user')
            ->leftJoin('sysuser', 'log_user.akun', '=', 'sysuser.fsysuserid')
            ->select(
                'log_user.floguserid',
                'log_user.ip',
                'log_user.akun',
                'log_user.komp',
                'log_user.login_date',
                'log_user.log_out_date',
                'sysuser.fname'
            );

        if ($request->filled('date_from')) {
            $query->whereDate('log_user.login_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('log_user.login_date', '<=', $request->date_to);
        }

        if ($request->filled('user')) {
            $query->where('log_user.akun', $request->user);
        }

        if ($request->filled('status')) {
            if ($request->status === 'online') {
                $query->whereNull('log_user.log_out_date');
            } elseif ($request->status === 'offline') {
                $query->whereNotNull('log_user.log_out_date');
            }
        }

        return $query->orderBy('log_user.login_date', 'desc');
    }
}
