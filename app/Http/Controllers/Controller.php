<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
            'period' => "Information\nTanggal transaksi tidak boleh kurang dari periode " . $this->getEditPeriodYm() . " !!!",
        ]);
    }

    protected function getPostedPeriodLockMessage($date, string $subject = 'Penerimaan ini'): ?string
    {
        if (! $this->isTransactionBeforeEditPeriod($date)) {
            return null;
        }

        return "Information\n{$subject} tidak dapat di-Edit/Delete.\nPeriode (" . Carbon::parse($date)->format('d-m-Y') . ") sudah di posting !!!";
    }
}
