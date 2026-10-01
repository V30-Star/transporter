<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EditPeriodeController extends Controller
{
    public function edit()
    {
        return view('editperiode.edit', [
            'pageTitle' => 'Edit Periode Posting',
            'fyrmth' => $this->getEditPeriodYm(),
        ]);
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'fyrmth' => ['required', 'regex:/^\d{6}$/'],
            ], [
                'fyrmth.required' => 'Periode wajib diisi.',
                'fyrmth.regex' => 'Periode harus format YYYYMM.',
            ]);

            $month = (int) substr($validated['fyrmth'], 4, 2);
            if ($month < 1 || $month > 12) {
                return back()->withInput()->with('error', "Information\nPeriode harus format YYYYMM yang valid.");
            }

            $year = (int) substr($validated['fyrmth'], 0, 4);
            $maxYear = (int) now()->format('Y') + 1;
            if ($year < 2000 || $year > $maxYear) {
                return back()->withInput()->with('error', "Information\nTahun periode harus antara 2000 dan {$maxYear}.");
            }

            DB::table('setini')->update([
                'fyrmth' => $validated['fyrmth'],
            ]);

            return redirect()
                ->route('dashboard')
                ->with('success', "Periode sudah di Update\njangan lupa diposting ulang.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Gagal mengupdate periode. Cek data.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate periode: ' . $e->getMessage());
        }
    }
}
