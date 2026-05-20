<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
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

    protected function ensureCreateDateWithinEditPeriod($date): void
    {
        if (! $this->isTransactionBeforeEditPeriod($date)) {
            return;
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
