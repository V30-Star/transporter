<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Converted from Delphi units:
 *   - udlgLaporanUangKasir.pas  (filter dialog: FormCreate, setQuery)
 *   - urptLaporanUangKasir.pas (ReportBuilder report bands + Excel export)
 *
 * "Laporan Uang Kasir" — daily cashier cash report, per branch:
 *   1. Transaksi     : sales (tranmt) grouped by payment method (fpembayaran)
 *   2. Pelunasan     : AR receipts (RCP) vs cash disbursements (BKK), netted
 *                      per account (trkasmt joined to account)
 *   3. Penjualan Net : cash-only sales total (used as the report's grand-total anchor)
 *
 * When the logged-in branch is HQ, an extra "Global / Akumulasi" section
 * aggregates the same three queries across every selected branch — this
 * mirrors the `if cBranchCode = 'HQ'` block and the *Global queries/bands
 * in the original report.
 */
class LaporanUangKasirService
{
    /**
     * Branch checklist for the filter form (mscabang).
     * Mirrors TdlgLaporanUangKasir.FormCreate: a user without the
     * "access all branches" permission only sees/can check their own branch,
     * and the whole list is disabled for them.
     */
    public function getBranchOptions(bool $canAccessAllBranches, ?string $userBranchCode): Collection
    {
        $userBranchCode = trim((string) ($userBranchCode ?? ''));

        return DB::table('mscabang')
            ->orderBy('fcabangkode')
            ->get(['fcabangkode', 'fcabangname'])
            ->map(fn ($row) => [
                'code' => trim((string) $row->fcabangkode),
                'name' => trim((string) $row->fcabangname),
                'checked' => $canAccessAllBranches || trim((string) $row->fcabangkode) === $userBranchCode,
            ]);
    }

    /** Mirrors `select now() tanggal` used to pre-fill dtpDateFrom. */
    public function serverDate(): Carbon
    {
        return Carbon::now();
    }

    /**
     * @param  array{
     *     tanggal: string,
     *     branch_codes: array<string>,
     *     kasir: string|null,
     *     hanya_tunai: bool,
     *     current_branch_code: string|null,
     *     total_branch_count: int,
     * }  $filters
     */
    public function generate(array $filters): array
    {
        $tanggal = Carbon::parse($filters['tanggal'])->toDateString();
        $branchCodes = array_values(array_filter(array_map('trim', (array) $filters['branch_codes'])));
        $kasir = trim((string) ($filters['kasir'] ?? ''));
        $hanyaTunai = (bool) ($filters['hanya_tunai'] ?? false);
        $currentBranchCode = strtoupper(trim((string) ($filters['current_branch_code'] ?? '')));

        $branchNames = $this->branchNames($branchCodes);

        $transaksi = $this->queryTransaksi($tanggal, $branchCodes, $kasir, $hanyaTunai)->groupBy('fbranchcode');
        $pelunasan = $this->queryPelunasan($tanggal, $branchCodes, $kasir, $hanyaTunai)->groupBy('fbranchcode');
        $penjualanNet = $this->queryPenjualanNet($tanggal, $branchCodes, $kasir)->keyBy('fbranchcode');

        $orderedCodes = collect($branchCodes)
            ->filter(fn ($code) => $transaksi->has($code) || $pelunasan->has($code))
            ->values();
        if ($orderedCodes->isEmpty()) {
            // No data at all for the selection — still render an (empty) block per selected branch.
            $orderedCodes = collect($branchCodes);
        }

        $sections = $orderedCodes->map(function ($code) use ($branchNames, $transaksi, $pelunasan, $penjualanNet) {
            $rows = $pelunasan->get($code, collect());
            $trx = $transaksi->get($code, collect());

            return [
                'code' => $code,
                'name' => $branchNames[$code] ?? $code,
                'transaksi' => $trx,
                'total_uang' => (float) $trx->sum('bayar'),
                'pelunasan' => $rows,
                'grand_total_pelunasan' => [
                    'rcp' => (float) $rows->sum('famountrcp'),
                    'bkk' => (float) $rows->sum('famountbkk'),
                    'net' => (float) $rows->sum('famountnet'),
                ],
                'penjualan_tunai' => (float) (optional($penjualanNet->get($code))->fbayar ?? 0.0),
            ];
        })->values();

        $global = null;
        if ($currentBranchCode === 'HQ') {
            $transaksiG = $this->queryTransaksiGlobal($tanggal, $branchCodes, $kasir, $hanyaTunai);
            $pelunasanG = $this->queryPelunasanGlobal($tanggal, $branchCodes, $kasir, $hanyaTunai);
            $penjualanNetG = $this->queryPenjualanNetGlobal($tanggal, $branchCodes, $kasir);

            $global = [
                'transaksi' => $transaksiG,
                'total_uang' => (float) $transaksiG->sum('bayar'),
                'pelunasan' => $pelunasanG,
                'grand_total_pelunasan' => [
                    'rcp' => (float) $pelunasanG->sum('famountrcp'),
                    'bkk' => (float) $pelunasanG->sum('famountbkk'),
                    'net' => (float) $pelunasanG->sum('famountnet'),
                ],
                'penjualan_tunai' => (float) ($penjualanNetG->fbayar ?? 0.0),
            ];
            $global['grand_total'] = $global['grand_total_pelunasan']['net'] + $global['penjualan_tunai'];
        }

        return [
            'sections' => $sections,
            'global' => $global,
            'meta' => [
                'tanggal' => $tanggal,
                'kasir_label' => $kasir !== '' ? $kasir : 'Semua',
                'hanya_tunai' => $hanyaTunai,
                'cabang_label' => count($branchCodes) >= (int) ($filters['total_branch_count'] ?? 0)
                    ? 'Semua'
                    : collect($branchCodes)->map(fn ($c) => $c.' - '.($branchNames[$c] ?? ''))->implode(' , '),
            ],
        ];
    }

    /**
     * Plain fixed-width text rendering of the report, meant to be sent
     * straight to a dot-matrix printer (raw/Generic-Text mode) instead of
     * relying on the browser's HTML print pipeline, which gets rasterized
     * to a low-resolution image and prints blurry on pin printers.
     *
     * @param  int  $width  Printer carriage width in characters — 40 for
     *                       narrow-carriage, 80 for standard letter-width
     *                       continuous form (10 cpi), 132 for wide-carriage
     *                       printers or condensed (17 cpi) mode.
     */
    public function toPlainText(array $data, int $width = 80): string
    {
        $wide = $width >= 72;
        $lines = [];

        $lines[] = $this->center('LAPORAN UANG KASIR', $width);
        $lines[] = str_repeat('=', $width);
        $lines[] = 'Tanggal : '.Carbon::parse($data['meta']['tanggal'])->translatedFormat('d F Y');
        $lines[] = 'Cabang  : '.$data['meta']['cabang_label'];
        $lines[] = 'Kasir   : '.$data['meta']['kasir_label'];
        if ($data['meta']['hanya_tunai']) {
            $lines[] = '** Hanya Uang Tunai Saja';
        }
        $lines[] = str_repeat('=', $width);
        $lines[] = '';

        foreach ($data['sections'] as $section) {
            $lines = array_merge($lines, $this->branchBlockText($section['code'].' - '.$section['name'], $section, $width, $wide, $section['name']));
            $lines[] = '';
        }

        if ($data['global']) {
            $lines = array_merge($lines, $this->branchBlockText('AKUMULASI (SELURUH CABANG TERPILIH)', $data['global'], $width, $wide, 'HQ'));
        }

        return implode("\n", $lines)."\n";
    }

    private function branchBlockText(string $title, array $section, int $width, bool $wide, string $grandTotalLabel): array
    {
        $lines = [];
        $lines[] = strtoupper($title);
        $lines[] = str_repeat('-', $width);
        $lines[] = 'Uang Penjualan';

        foreach ($section['transaksi'] as $t) {
            $lines[] = '  '.$this->twoCol($t->fpembayaran, $this->rp((float) $t->bayar), $width - 2);
        }
        $lines[] = $this->twoCol('Total Uang', $this->rp((float) $section['total_uang']), $width);
        $lines[] = '';

        if ($section['pelunasan']->isNotEmpty()) {
            $lines[] = 'Pelunasan / Pengeluaran Kas';

            if ($wide) {
                $lines[] = $this->fourCol('Account', 'Pelunasan', 'Pengel.Kas', 'Saldo', $width);
                $lines[] = str_repeat('-', $width);
                foreach ($section['pelunasan'] as $p) {
                    $lines[] = $this->fourCol($p->faccname, $this->rp((float) $p->famountrcp), $this->rp((float) $p->famountbkk), $this->rp((float) $p->famountnet), $width);
                }
            } else {
                // Narrow carriage: stack each field instead of cramming four columns.
                foreach ($section['pelunasan'] as $p) {
                    $lines[] = mb_substr($p->faccname, 0, $width);
                    $lines[] = '  Pelunasan  : '.$this->rp((float) $p->famountrcp);
                    $lines[] = '  Pengel.Kas : '.$this->rp((float) $p->famountbkk);
                    $lines[] = '  Saldo      : '.$this->rp((float) $p->famountnet);
                }
            }

            $lines[] = str_repeat('-', $width);
            $gt = $section['grand_total_pelunasan'];
            $lines[] = $this->twoCol('Grand Total Pelunasan', $this->rp((float) $gt['net']), $width);
            $lines[] = $this->twoCol('GT. Penjualan Tunai', $this->rp((float) $section['penjualan_tunai']), $width);
            $lines[] = str_repeat('=', $width);
            $lines[] = $this->twoCol('Grand Total '.$grandTotalLabel, $this->rp((float) ($gt['net'] + $section['penjualan_tunai'])), $width);
            $lines[] = str_repeat('=', $width);
        }

        return $lines;
    }

    private function rp(float $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    /** Left-aligned label + right-aligned amount, padded to exactly $width chars. */
    private function twoCol(string $left, string $right, int $width): string
    {
        $left = mb_substr($left, 0, max(1, $width - mb_strlen($right) - 1));
        $pad = max(1, $width - mb_strlen($left) - mb_strlen($right));

        return $left.str_repeat(' ', $pad).$right;
    }

    /** Four evenly-sized columns for the Pelunasan table on wide-carriage printers. */
    private function fourCol(string $a, string $b, string $c, string $d, int $width): string
    {
        $numW = 15;
        $nameW = max(10, $width - ($numW * 3) - 3);

        return str_pad(mb_substr($a, 0, $nameW), $nameW).' '.
               str_pad($b, $numW, ' ', STR_PAD_LEFT).' '.
               str_pad($c, $numW, ' ', STR_PAD_LEFT).' '.
               str_pad($d, $numW, ' ', STR_PAD_LEFT);
    }

    private function center(string $text, int $width): string
    {
        $pad = max(0, intdiv($width - mb_strlen($text), 2));

        return str_repeat(' ', $pad).$text;
    }

    private function branchNames(array $branchCodes): array
    {
        return DB::table('mscabang')
            ->whereIn('fcabangkode', $branchCodes)
            ->pluck('fcabangname', 'fcabangkode')
            ->mapWithKeys(fn ($name, $code) => [trim((string) $code) => trim((string) $name)])
            ->all();
    }

    /** Query 1 — quTransaksi: sales grouped by branch + payment method. */
    private function queryTransaksi(string $tanggal, array $branchCodes, string $kasir, bool $hanyaTunai): Collection
    {
        return DB::table('tranmt')
            ->selectRaw("TRIM(fbranchcode) AS fbranchcode, CASE WHEN COALESCE(TRIM(fpembayaran),'') = '' THEN CAST('Kredit' AS VARCHAR(15)) ELSE TRIM(fpembayaran) END AS fpembayaran, SUM(COALESCE(famountso, 0)) AS bayar")
            ->whereDate('fsodate', $tanggal)
            ->where('fgrosir', '0')
            ->whereIn('fbranchcode', $branchCodes)
            ->when($hanyaTunai, fn ($q) => $q->whereRaw("UPPER(TRIM(fpembayaran)) = 'TUNAI'"))
            ->when($kasir !== '', fn ($q) => $q->whereRaw("TRIM(fuserid) ILIKE ?", ['%' . $kasir . '%']))
            ->groupByRaw("TRIM(fbranchcode), CASE WHEN COALESCE(TRIM(fpembayaran),'') = '' THEN CAST('Kredit' AS VARCHAR(15)) ELSE TRIM(fpembayaran) END")
            ->orderByRaw("TRIM(fbranchcode), fpembayaran")
            ->get()
            ->map(function ($row) {
                $row->fbranchcode = trim((string) $row->fbranchcode);
                $row->fpembayaran = trim((string) $row->fpembayaran);
                $row->bayar = (float) $row->bayar;
                return $row;
            });
    }

    /** Query 2 — QuPelunasanFaktur: RCP vs BKK netted per account, per branch. */
    private function queryPelunasan(string $tanggal, array $branchCodes, string $kasir, bool $hanyaTunai): Collection
    {
        $rcp = $this->pelunasanLeg('RCP', $tanggal, $branchCodes, $kasir, $hanyaTunai, true);
        $bkk = $this->pelunasanLeg('BKK', $tanggal, $branchCodes, $kasir, false, true);

        return $this->fullOuterNet($rcp, $bkk, true);
    }

    private function pelunasanLeg(string $tranCode, string $tanggal, array $branchCodes, string $kasir, bool $hanyaTunai, bool $perBranch): Collection
    {
        $query = DB::table('trkasmt as m')
            ->join('account as a', 'm.faccountno', '=', 'a.faccount')
            ->where('m.ftrancode', $tranCode)
            ->whereDate('m.fkasmtdate', $tanggal)
            ->whereIn('m.fbranchcode', $branchCodes)
            ->when($hanyaTunai, fn ($q) => $q->where(function ($sub) {
                $sub->whereIn('m.faccountno', function ($s) {
                    $s->select(DB::raw("TRIM(fmastercode)"))->from('tbmaster')->whereIn(DB::raw("TRIM(ftblcode)"), ['CASHACCOUNT', 'CASHBANK']);
                })->orWhereRaw("LOWER(TRIM(a.faccname)) LIKE '%kas%'");
            }))
            ->when($kasir !== '', fn ($q) => $q->whereRaw("TRIM(m.fuserid) ILIKE ?", ['%' . $kasir . '%']));

        if ($perBranch) {
            return $query->selectRaw("TRIM(m.fbranchcode) AS fbranchcode, TRIM(m.faccountno) AS faccountno, TRIM(a.faccname) AS faccname, SUM(COALESCE(m.famountpay, 0)) AS famountpay")
                ->groupByRaw("TRIM(m.fbranchcode), TRIM(m.faccountno), TRIM(a.faccname)")
                ->get()
                ->map(function ($row) {
                    $row->fbranchcode = trim((string) $row->fbranchcode);
                    $row->faccountno = trim((string) $row->faccountno);
                    $row->faccname = trim((string) $row->faccname);
                    $row->famountpay = (float) $row->famountpay;
                    return $row;
                });
        }

        return $query->selectRaw("TRIM(m.faccountno) AS faccountno, TRIM(a.faccname) AS faccname, SUM(COALESCE(m.famountpay, 0)) AS famountpay")
            ->groupByRaw("TRIM(m.faccountno), TRIM(a.faccname)")
            ->get()
            ->map(function ($row) {
                $row->faccountno = trim((string) $row->faccountno);
                $row->faccname = trim((string) $row->faccname);
                $row->famountpay = (float) $row->famountpay;
                return $row;
            });
    }

    /**
     * PHP-side equivalent of the Delphi "... full outer join ... on
     * faccountno (and fbranchcode)" — nets RCP against BKK per account,
     * since a plain SQL FULL OUTER JOIN isn't portable across every driver
     * Laravel's query builder targets.
     */
    private function fullOuterNet(Collection $rcp, Collection $bkk, bool $perBranch): Collection
    {
        $key = fn ($row) => $perBranch ? ($row->fbranchcode ?? '').'|'.($row->faccountno ?? '') : ($row->faccountno ?? '');

        $rows = [];
        foreach ($rcp as $r) {
            $rows[$key($r)] = [
                'fbranchcode' => $r->fbranchcode ?? null,
                'faccountno' => $r->faccountno,
                'faccname' => $r->faccname,
                'famountrcp' => (float) $r->famountpay,
                'famountbkk' => 0.0,
            ];
        }
        foreach ($bkk as $b) {
            $k = $key($b);
            if (! isset($rows[$k])) {
                $rows[$k] = [
                    'fbranchcode' => $b->fbranchcode ?? null,
                    'faccountno' => $b->faccountno,
                    'faccname' => $b->faccname,
                    'famountrcp' => 0.0,
                    'famountbkk' => 0.0,
                ];
            }
            $rows[$k]['famountbkk'] = (float) $b->famountpay;
        }

        return collect($rows)
            ->map(function ($row) {
                $row['famountnet'] = (float) ($row['famountrcp'] - $row['famountbkk']);

                return (object) $row;
            })
            ->sortBy($perBranch ? ['fbranchcode', 'faccountno'] : 'faccountno')
            ->values();
    }

    /** Query 3 — QuPenjualanNet: cash-only sales total, per branch. */
    private function queryPenjualanNet(string $tanggal, array $branchCodes, string $kasir): Collection
    {
        return DB::table('tranmt')
            ->selectRaw("TRIM(fbranchcode) AS fbranchcode, SUM(COALESCE(famountso, 0)) AS fbayar")
            ->whereDate('fsodate', $tanggal)
            ->where('fgrosir', '0')
            ->whereRaw("UPPER(TRIM(fpembayaran)) = 'TUNAI'")
            ->whereIn('fbranchcode', $branchCodes)
            ->when($kasir !== '', fn ($q) => $q->whereRaw("TRIM(fuserid) ILIKE ?", ['%' . $kasir . '%']))
            ->groupByRaw("TRIM(fbranchcode)")
            ->get()
            ->map(function ($row) {
                $row->fbranchcode = trim((string) $row->fbranchcode);
                $row->fbayar = (float) $row->fbayar;
                return $row;
            });
    }

    /** HQ "Akumulasi" — the three queries above, aggregated across all selected branches. */
    private function queryTransaksiGlobal(string $tanggal, array $branchCodes, string $kasir, bool $hanyaTunai): Collection
    {
        return DB::table('tranmt')
            ->selectRaw("CAST('HQ' AS VARCHAR(10)) AS fbranchcode, CASE WHEN COALESCE(TRIM(fpembayaran),'') = '' THEN CAST('Kredit' AS VARCHAR(15)) ELSE TRIM(fpembayaran) END AS fpembayaran, SUM(COALESCE(famountso, 0)) AS bayar")
            ->whereDate('fsodate', $tanggal)
            ->where('fgrosir', '0')
            ->whereIn('fbranchcode', $branchCodes)
            ->when($kasir !== '', fn ($q) => $q->whereRaw("TRIM(fuserid) ILIKE ?", ['%' . $kasir . '%']))
            ->when($hanyaTunai, fn ($q) => $q->whereRaw("UPPER(TRIM(fpembayaran)) = 'TUNAI'"))
            ->groupByRaw("CASE WHEN COALESCE(TRIM(fpembayaran),'') = '' THEN CAST('Kredit' AS VARCHAR(15)) ELSE TRIM(fpembayaran) END")
            ->orderByRaw("fpembayaran")
            ->get()
            ->map(function ($row) {
                $row->fbranchcode = 'HQ';
                $row->fpembayaran = trim((string) $row->fpembayaran);
                $row->bayar = (float) $row->bayar;
                return $row;
            });
    }

    private function queryPelunasanGlobal(string $tanggal, array $branchCodes, string $kasir, bool $hanyaTunai): Collection
    {
        $rcp = $this->pelunasanLeg('RCP', $tanggal, $branchCodes, $kasir, $hanyaTunai, false);
        $bkk = $this->pelunasanLeg('BKK', $tanggal, $branchCodes, $kasir, false, false);

        return $this->fullOuterNet($rcp, $bkk, false);
    }

    private function queryPenjualanNetGlobal(string $tanggal, array $branchCodes, string $kasir): ?object
    {
        $res = DB::table('tranmt')
            ->selectRaw("SUM(COALESCE(famountso, 0)) AS fbayar")
            ->whereDate('fsodate', $tanggal)
            ->where('fgrosir', '0')
            ->whereRaw("UPPER(TRIM(fpembayaran)) = 'TUNAI'")
            ->whereIn('fbranchcode', $branchCodes)
            ->when($kasir !== '', fn ($q) => $q->whereRaw("TRIM(fuserid) ILIKE ?", ['%' . $kasir . '%']))
            ->first();

        if ($res) {
            $res->fbayar = (float) ($res->fbayar ?? 0.0);
        }

        return $res;
    }
}
