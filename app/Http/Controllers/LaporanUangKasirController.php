<?php

namespace App\Http\Controllers;

use App\Services\LaporanUangKasirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanUangKasirController extends Controller
{
    public function __construct(private LaporanUangKasirService $service)
    {
    }

    public function index()
    {
        $canAccessAllBranches = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();
        $branchOptions = $this->service->getBranchOptions($canAccessAllBranches, $userBranchCode);
        $serverDate = $this->service->serverDate()->toDateString();

        return view('laporanuangkasir.index', [
            'branchOptions' => $branchOptions,
            'canAccessAllBranches' => $canAccessAllBranches,
            'userBranchCode' => $userBranchCode,
            'serverDate' => $serverDate,
            // Compatibility aliases
            'branches' => $branchOptions,
            'isAuthorized' => $canAccessAllBranches,
            'date' => $serverDate,
        ]);
    }

    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $data = $this->service->generate($filters);

        $company = function_exists('company_setting') ? (company_setting() ?: (object) []) : (object) [];
        $operator = auth('sysuser')->user()?->fname ?? auth()->user()?->fname ?? 'User';
        $printedAt = now();

        return view('laporanuangkasir.print', compact('data', 'company', 'operator', 'printedAt') + [
            // Aliases for template compatibility
            'date' => $filters['tanggal'],
            'kasir' => $filters['kasir'],
            'onlyCash' => $filters['hanya_tunai'],
        ]);
    }

    public function show(Request $request)
    {
        return $this->print($request);
    }

    public function printRaw(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $data = $this->service->generate($filters);

        $width = (int) $request->query('width', 80);
        $escp = $request->boolean('escp');

        $text = $this->service->toPlainText($data, $width);

        if ($escp) {
            $esc = "\x1B";
            $reset = $esc.'@';                     // ESC @  — initialize printer
            $nlq = $esc.'x'.chr(1);                // ESC x 1 — Near Letter Quality on
            $condensed = $width > 80 ? "\x0F" : ''; // SI — condensed (17 cpi)
            $formFeed = "\x0C";                     // form-feed eject
            $text = $reset.$nlq.$condensed.$text.$formFeed;
        }

        $filename = 'laporan-uang-kasir-'.$data['meta']['tanggal'].($escp ? '.prn' : '.txt');

        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $data = $this->service->generate($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Uang Kasir');
        $row = 1;

        foreach ($data['sections'] as $section) {
            $row = $this->writeBranchBlock($sheet, $row, $section['name'], $section['transaksi'], $section['total_uang'], $section['pelunasan'], $section['grand_total_pelunasan'], $section['penjualan_tunai']);
            $row += 2;
        }

        if ($data['global']) {
            $g = $data['global'];
            $row += 1;
            $sheet->setCellValue("A{$row}", 'Akumulasi');
            $row++;
            $row = $this->writeBranchBlock($sheet, $row, null, $g['transaksi'], $g['total_uang'], $g['pelunasan'], $g['grand_total_pelunasan'], $g['penjualan_tunai'], 'HQ');
        }

        $filename = 'laporan-uang-kasir-'.$data['meta']['tanggal'].'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function writeBranchBlock($sheet, int $row, ?string $branchLabel, $transaksi, float $totalUang, $pelunasan, array $grandTotalPelunasan, float $penjualanTunai, ?string $grandTotalLabel = null): int
    {
        if ($branchLabel !== null) {
            $sheet->setCellValue("A{$row}", $branchLabel);
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'Uang Penjualan : ');
        $row++;

        foreach ($transaksi as $t) {
            $sheet->setCellValue("A{$row}", $t->fpembayaran);
            $sheet->setCellValue("B{$row}", $t->bayar);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Uang');
        $sheet->setCellValue("B{$row}", $totalUang);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Account');
        $sheet->setCellValue("B{$row}", 'Pelunasan Faktur');
        $sheet->setCellValue("C{$row}", 'Pengeluaran Kas');
        $sheet->setCellValue("D{$row}", 'Saldo');
        $row++;

        foreach ($pelunasan as $p) {
            $sheet->setCellValue("A{$row}", $p->faccname);
            $sheet->setCellValue("B{$row}", $p->famountrcp);
            $sheet->setCellValue("C{$row}", $p->famountbkk);
            $sheet->setCellValue("D{$row}", $p->famountnet);
            $row++;
        }

        if ($grandTotalPelunasan['rcp'] > 0 || $penjualanTunai > 0 || $totalUang > 0) {
            $sheet->setCellValue("A{$row}", 'Grand Total Pelunasan');
            $sheet->setCellValue("B{$row}", $grandTotalPelunasan['rcp']);
            $sheet->setCellValue("C{$row}", $grandTotalPelunasan['bkk']);
            $sheet->setCellValue("D{$row}", $grandTotalPelunasan['net']);
            $row++;

            $sheet->setCellValue("A{$row}", 'GT. Penjualan Tunai');
            $sheet->setCellValue("D{$row}", $penjualanTunai);
            $row++;

            $sheet->setCellValue("A{$row}", 'Grand Total '.($grandTotalLabel ?? $branchLabel));
            $sheet->setCellValue("D{$row}", $grandTotalPelunasan['net'] + $penjualanTunai);
            $row++;
        }

        return $row;
    }

    private function validatedFilters(Request $request): array
    {
        $rawDate = $request->input('tanggal') ?: $request->input('date') ?: now()->toDateString();
        $branchCodes = (array) $request->input('branch_codes', []);

        if (empty($branchCodes)) {
            if ($this->canAccessAllBranches()) {
                $branchCodes = DB::table('mscabang')->pluck('fcabangkode')->map(fn ($c) => trim((string) $c))->all();
            } else {
                $code = $this->getCurrentBranchCode();
                $branchCodes = $code ? [$code] : [];
            }
        }

        $kasir = trim((string) $request->input('kasir', ''));
        $hanyaTunai = $request->boolean('hanya_tunai') || $request->boolean('only_cash');
        $currentBranchCode = $this->getCurrentBranchCode();
        $totalBranches = DB::table('mscabang')->count();

        return [
            'tanggal' => $rawDate,
            'branch_codes' => $branchCodes,
            'kasir' => $kasir !== '' ? $kasir : null,
            'hanya_tunai' => $hanyaTunai,
            'current_branch_code' => $currentBranchCode,
            'total_branch_count' => $totalBranches,
        ];
    }
}
