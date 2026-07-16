<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function getRestrictedPermissions(): array
    {
        return array_filter(array_map('trim', explode(',', (string) session('user_restricted_permissions', ''))));
    }

    protected function hasRestrictedPermission(string $permission): bool
    {
        return in_array($permission, $this->getRestrictedPermissions(), true);
    }

    protected function canChangeTransactionDate(): bool
    {
        return $this->hasRestrictedPermission('BolehGantiTanggal');
    }

    protected function canAccessAllBranches(): bool
    {
        return $this->hasRestrictedPermission('semuacabang');
    }

    protected function getCurrentBranchCode(): ?string
    {
        $rawBranch = Auth::guard('sysuser')->user()?->fcabang
            ?? Auth::user()?->fcabang
            ?? session('fcabang');

        $needle = trim((string) $rawBranch);
        if ($needle === '') {
            return null;
        }

        if (is_numeric($needle)) {
            $code = DB::table('mscabang')
                ->where('fcabangid', (int) $needle)
                ->value('fcabangkode');

            return filled($code) ? trim((string) $code) : $needle;
        }

        $code = DB::table('mscabang')
            ->whereRaw('LOWER(TRIM(fcabangkode)) = LOWER(?)', [$needle])
            ->value('fcabangkode');

        if (! filled($code)) {
            $code = DB::table('mscabang')
                ->whereRaw('LOWER(TRIM(fcabangname)) = LOWER(?)', [$needle])
                ->value('fcabangkode');
        }

        return filled($code) ? trim((string) $code) : $needle;
    }

    protected function applyBranchVisibilityScope($query, string $column = 'fbranchcode')
    {
        if ($this->canAccessAllBranches()) {
            return $query;
        }

        $branchCode = $this->getCurrentBranchCode();
        if (! filled($branchCode)) {
            return $query;
        }

        if ($query instanceof EloquentBuilder || $query instanceof QueryBuilder) {
            $query->where($column, $branchCode);
        }

        return $query;
    }

    protected function resolveBranchContext($branch = null): array
    {
        $needle = trim((string) ($branch ?? ''));

        if ($needle === '') {
            $needle = trim((string) ($this->getCurrentBranchCode() ?? ''));
        }

        if ($needle === '') {
            return [
                'fbranchcode' => '',
                'fcabang' => '',
            ];
        }

        $branchQuery = DB::table('mscabang');

        if (is_numeric($needle)) {
            $branchQuery->where('fcabangid', (int) $needle);
        } else {
            $branchQuery->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(TRIM(fcabangkode)) = LOWER(?)', [$needle])
                    ->orWhereRaw('LOWER(TRIM(fcabangname)) = LOWER(?)', [$needle]);
            });
        }

        $resolved = $branchQuery->first(['fcabangkode', 'fcabangname']);

        return [
            'fbranchcode' => trim((string) ($resolved->fcabangkode ?? $needle)),
            'fcabang' => trim((string) ($resolved->fcabangname ?? $needle)),
        ];
    }

    protected function getEditPeriodYm(): string
    {
        $raw = trim((string) DB::table('setini')->value('fyrmth'));

        if (! preg_match('/^\d{6}$/', $raw)) {
            return now()->format('Ym');
        }

        return $raw;
    }

    protected function getEditPeriodStart(): Carbon
    {
        return Carbon::createFromFormat('Ym', $this->getEditPeriodYm())->startOfMonth();
    }

    protected function isTransactionBeforeEditPeriod($date): bool
    {
        if (empty($date)) {
            return false;
        }

        return Carbon::parse($date)->startOfDay()->lt($this->getEditPeriodStart());
    }

    protected function ensureCreateDateWithinEditPeriod($date, $originalDate = null): void
    {
        if (empty($date)) {
            return;
        }

        if (! $this->isTransactionBeforeEditPeriod($date)) {
            $submittedDate = Carbon::parse($date)->startOfDay();
            $today = now()->startOfDay();

            if ($this->canChangeTransactionDate()) {
                return;
            }

            if (! empty($originalDate)) {
                $existingDate = Carbon::parse($originalDate)->startOfDay();

                if ($submittedDate->equalTo($existingDate)) {
                    return;
                }
            }

            if ($submittedDate->equalTo($today)) {
                return;
            }

            throw ValidationException::withMessages([
                'fdate' => "Information\nTanggal transaksi harus sama dengan hari ini (" . $today->format('d-m-Y') . ") !!!",
            ]);
        }

        throw ValidationException::withMessages([
            'period' => "Information\nPeriode " . Carbon::parse($originalDate ?: $date)->format('Ym') . " sudah diposting !!!",
        ]);
    }

    protected function getPostedPeriodLockMessage($date, string $subject = 'Penerimaan ini'): ?string
    {
        if (! $this->isTransactionBeforeEditPeriod($date)) {
            return null;
        }

        return "Information\n{$subject} tidak dapat di-Edit/Delete.\nPeriode (" . Carbon::parse($date)->format('d-m-Y') . ") sudah di posting !!!";
    }

    protected function getClearedGiroLockMessage($header, string $subject = 'Transaksi ini'): ?string
    {
        if ((string) ($header->fgiromundur ?? '0') === '1' && (string) ($header->fstatusgiro ?? '0') === '1') {
            return "Information\n{$subject} tidak dapat di-Edit/Delete.\nGiro mundur sudah cair !!!";
        }

        return null;
    }

    protected function stockMinusAllowsForce(): bool
    {
        return stock_boleh_minus();
    }

    protected function validateStockMinusLines(array $lines, bool $forceSave = false)
    {
        $needs = [];

        foreach ($lines as $line) {
            $prdcode = trim((string) ($line['fprdcode'] ?? ''));
            $whcode = trim((string) ($line['fwhcode'] ?? ''));
            $qty = (float) ($line['qty_kecil'] ?? 0);

            if ($prdcode === '' || $whcode === '' || $qty <= 0) {
                continue;
            }

            $key = $prdcode . '|' . $whcode;
            if (! isset($needs[$key])) {
                $needs[$key] = [
                    'fprdcode' => $prdcode,
                    'fwhcode' => $whcode,
                    'qty' => 0.0,
                ];
            }
            $needs[$key]['qty'] += $qty;
        }

        if (empty($needs)) {
            return null;
        }

        $shortages = [];
        foreach ($needs as $need) {
            $stock = DB::table('prdwh as w')
                ->join('msprd as p', 'p.fprdcode', '=', 'w.fprdcode')
                ->where('p.fprdcode', $need['fprdcode'])
                ->where('w.fwhcode', $need['fwhcode'])
                ->first(['w.*', 'p.fprdname']);

            $available = (float) ($stock->fsaldo ?? 0);
            if ($available + 0.000001 >= $need['qty']) {
                continue;
            }

            $shortages[] = [
                'fprdcode' => $need['fprdcode'],
                'fprdname' => trim((string) ($stock->fprdname ?? '')),
                'fwhcode' => $need['fwhcode'],
                'available' => $available,
                'required' => $need['qty'],
            ];
        }

        if (empty($shortages) || ($forceSave && $this->stockMinusAllowsForce())) {
            return null;
        }

        $products = array_map(function ($row) {
            $name = $row['fprdname'] !== '' ? ' - ' . $row['fprdname'] : '';
            $available = rtrim(rtrim(number_format($row['available'], 4, '.', ''), '0'), '.');

            return $row['fprdcode'] . $name . ' - Stok Hanya ' . $available;
        }, $shortages);

        $message = "Produk\n" . implode("\n", $products) . "\nQty Stok tidak cukup digudang";

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'insufficient_stock',
                'message' => $message . ($this->stockMinusAllowsForce() ? "\nApakah anda ingin melanjutkan penyimpanan?" : ''),
                'allow_force' => $this->stockMinusAllowsForce(),
                'products' => $shortages,
            ], 422);
        }

        return back()->withInput()->with('error', $message);
    }

    protected function buildStockMinusLinesFromStockRows(array $rows, string $whcode): array
    {
        return array_values(array_map(function ($row) use ($whcode) {
            return [
                'fprdcode' => $row['fprdcode'] ?? null,
                'fwhcode' => $whcode,
                'qty_kecil' => max(0, (float) ($row['fqtykecil'] ?? 0)),
            ];
        }, $rows));
    }

    protected function buildStockMinusLinesFromNetChange(array $newRows, string $newWhcode, array $oldRows = [], ?string $oldWhcode = null): array
    {
        $net = [];
        $oldWhcode = $oldWhcode ?? $newWhcode;

        foreach ($oldRows as $row) {
            $prdcode = trim((string) ($row['fprdcode'] ?? ''));
            if ($prdcode === '' || $oldWhcode === '') {
                continue;
            }
            $key = $prdcode . '|' . $oldWhcode;
            $net[$key] = ($net[$key] ?? 0) + (float) ($row['fqtykecil'] ?? 0);
        }

        foreach ($newRows as $row) {
            $prdcode = trim((string) ($row['fprdcode'] ?? ''));
            if ($prdcode === '' || $newWhcode === '') {
                continue;
            }
            $key = $prdcode . '|' . $newWhcode;
            $net[$key] = ($net[$key] ?? 0) - (float) ($row['fqtykecil'] ?? 0);
        }

        $lines = [];
        foreach ($net as $key => $qty) {
            if ($qty <= 0) {
                continue;
            }
            [$prdcode, $whcode] = explode('|', $key, 2);
            $lines[] = [
                'fprdcode' => $prdcode,
                'fwhcode' => $whcode,
                'qty_kecil' => $qty,
            ];
        }

        return $lines;
    }

    protected function fetchStockDetailRows(string $fstockmtno): array
    {
        return DB::table('trstockdt')
            ->where('fstockmtno', $fstockmtno)
            ->get(['fprdcode', 'fqtykecil'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    protected function buildStockMinusLinesForOutChange(array $newRows, string $newWhcode, array $oldRows = [], ?string $oldWhcode = null): array
    {
        $net = [];
        $oldWhcode = $oldWhcode ?? $newWhcode;

        foreach ($newRows as $row) {
            $prdcode = trim((string) ($row['fprdcode'] ?? ''));
            if ($prdcode === '' || $newWhcode === '') {
                continue;
            }
            $key = $prdcode . '|' . $newWhcode;
            $net[$key] = ($net[$key] ?? 0) + (float) ($row['fqtykecil'] ?? 0);
        }

        foreach ($oldRows as $row) {
            $prdcode = trim((string) ($row['fprdcode'] ?? ''));
            if ($prdcode === '' || $oldWhcode === '') {
                continue;
            }
            $key = $prdcode . '|' . $oldWhcode;
            $net[$key] = ($net[$key] ?? 0) - (float) ($row['fqtykecil'] ?? 0);
        }

        $lines = [];
        foreach ($net as $key => $qty) {
            if ($qty <= 0) {
                continue;
            }
            [$prdcode, $whcode] = explode('|', $key, 2);
            $lines[] = [
                'fprdcode' => $prdcode,
                'fwhcode' => $whcode,
                'qty_kecil' => $qty,
            ];
        }

        return $lines;
    }

    protected function buildStockMinusLinesFromEffects(array $effects): array
    {
        $net = [];

        foreach ($effects as $effect) {
            $rows = $effect['rows'] ?? [];
            $whcode = trim((string) ($effect['fwhcode'] ?? ''));
            $sign = (float) ($effect['sign'] ?? 0);

            if ($whcode === '' || $sign == 0.0) {
                continue;
            }

            foreach ($rows as $row) {
                $prdcode = trim((string) ($row['fprdcode'] ?? ''));
                if ($prdcode === '') {
                    continue;
                }
                $key = $prdcode . '|' . $whcode;
                $net[$key] = ($net[$key] ?? 0) + ($sign * (float) ($row['fqtykecil'] ?? 0));
            }
        }

        $lines = [];
        foreach ($net as $key => $qty) {
            if ($qty <= 0) {
                continue;
            }
            [$prdcode, $whcode] = explode('|', $key, 2);
            $lines[] = [
                'fprdcode' => $prdcode,
                'fwhcode' => $whcode,
                'qty_kecil' => $qty,
            ];
        }

        return $lines;
    }

    protected function buildStockMinusLinesForSignedRows(array $newRows, string $newWhcode, array $oldRows = [], ?string $oldWhcode = null): array
    {
        $net = [];
        $oldWhcode = $oldWhcode ?? $newWhcode;

        foreach ($newRows as $row) {
            $prdcode = trim((string) ($row['fprdcode'] ?? ''));
            if ($prdcode === '' || $newWhcode === '') {
                continue;
            }
            $key = $prdcode . '|' . $newWhcode;
            $net[$key] = ($net[$key] ?? 0) - (float) ($row['fqtykecil'] ?? 0);
        }

        foreach ($oldRows as $row) {
            $prdcode = trim((string) ($row['fprdcode'] ?? ''));
            if ($prdcode === '' || $oldWhcode === '') {
                continue;
            }
            $key = $prdcode . '|' . $oldWhcode;
            $net[$key] = ($net[$key] ?? 0) + (float) ($row['fqtykecil'] ?? 0);
        }

        $lines = [];
        foreach ($net as $key => $qty) {
            if ($qty <= 0) {
                continue;
            }
            [$prdcode, $whcode] = explode('|', $key, 2);
            $lines[] = [
                'fprdcode' => $prdcode,
                'fwhcode' => $whcode,
                'qty_kecil' => $qty,
            ];
        }

        return $lines;
    }
}
