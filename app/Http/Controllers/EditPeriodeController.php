<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EditPeriodeController extends Controller
{
    public function edit()
    {
        return view('editperiode.edit', [
            'pageTitle' => 'Edit Periode',
            'fyrmth' => $this->getEditPeriodYm(),
        ]);
    }

    public function update(Request $request)
    {
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

        DB::table('setini')->update([
            'fyrmth' => $validated['fyrmth'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'PERIODE EDIT BERHASIL DIUPDATE.');
    }
}
