<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    public function edit()
    {
        $setini = DB::table('setini')->first();

        return view('systemsetting.edit', [
            'pageTitle' => 'System Setting',
            'setting' => $setini,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'fproject' => 'nullable|string|max:200',
            'fcity' => 'nullable|string|max:100',
            'falamat1' => 'nullable|string|max:200',
            'falamat2' => 'nullable|string|max:200',
            'ftelp' => 'nullable|string|max:50',
            'ffax' => 'nullable|string|max:50',
            'fnpwp' => 'nullable|string|max:50',
            'falamat1npwp' => 'nullable|string|max:200',
            'falamat2npwp' => 'nullable|string|max:200',
            'fnamattdfakturpenjualan' => 'nullable|string|max:100',
            'fnamattdfakturpenjualan2' => 'nullable|string|max:100',
            'fnamattdpo' => 'nullable|string|max:100',
            'fnamattdpo2' => 'nullable|string|max:100',
            'fppntarif' => 'nullable|numeric|min:0|max:100',
            'fstokbolehminus' => 'nullable|in:0,1',
        ]);

        $updateData = [
            'fproject' => $validated['fproject'] ?? '',
            'fcity' => $validated['fcity'] ?? '',
            'falamat1' => $validated['falamat1'] ?? '',
            'falamat2' => $validated['falamat2'] ?? '',
            'ftelp' => $validated['ftelp'] ?? null,
            'ffax' => $validated['ffax'] ?? null,
            'fnpwp' => $validated['fnpwp'] ?? null,
            'falamat1npwp' => $validated['falamat1npwp'] ?? null,
            'falamat2npwp' => $validated['falamat2npwp'] ?? null,
            'fnamattdfakturpenjualan' => $validated['fnamattdfakturpenjualan'] ?? '',
            'fnamattdfakturpenjualan2' => $validated['fnamattdfakturpenjualan2'] ?? null,
            'fnamattdpo' => $validated['fnamattdpo'] ?? '',
            'fnamattdpo2' => $validated['fnamattdpo2'] ?? null,
        ];

        if (isset($validated['fppntarif'])) {
            $updateData['fppntarif'] = $validated['fppntarif'];
        }
        if (isset($validated['fstokbolehminus'])) {
            $updateData['fstokbolehminus'] = $validated['fstokbolehminus'];
        }

        DB::table('setini')->update($updateData);

        return redirect()
            ->route('systemsetting.edit')
            ->with('success', 'System Setting berhasil disimpan.');
    }
}
