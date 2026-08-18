<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    public function edit()
    {
        $setini = DB::table('setini')->first();
        if ($setini) {
            $setini->falamat1 = decrypt_value($setini->falamat1 ?? '');
            $setini->falamat2 = decrypt_value($setini->falamat2 ?? '');
            $setini->fnpwp = decrypt_value($setini->fnpwp ?? '');
            $setini->falamat1npwp = decrypt_value($setini->falamat1npwp ?? '');
            $setini->falamat2npwp = decrypt_value($setini->falamat2npwp ?? '');
        }

        return view('systemsetting.edit', [
            'pageTitle' => 'System Setting',
            'setting' => $setini,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
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
        ]);

        $alamat1 = trim($validated['falamat1'] ?? '');
        $alamat2 = trim($validated['falamat2'] ?? '');
        $npwp = trim($validated['fnpwp'] ?? '');
        $alamat1npwp = trim($validated['falamat1npwp'] ?? '');
        $alamat2npwp = trim($validated['falamat2npwp'] ?? '');

        $updateData = [
            'fcity' => $validated['fcity'] ?? '',
            'falamat1' => $alamat1 !== '' ? Crypt::encryptString($alamat1) : '',
            'falamat2' => $alamat2 !== '' ? Crypt::encryptString($alamat2) : '',
            'ftelp' => $validated['ftelp'] ?? null,
            'ffax' => $validated['ffax'] ?? null,
            'fnpwp' => $npwp !== '' ? Crypt::encryptString($npwp) : null,
            'falamat1npwp' => $alamat1npwp !== '' ? Crypt::encryptString($alamat1npwp) : null,
            'falamat2npwp' => $alamat2npwp !== '' ? Crypt::encryptString($alamat2npwp) : null,
            'fnamattdfakturpenjualan' => $validated['fnamattdfakturpenjualan'] ?? '',
            'fnamattdfakturpenjualan2' => $validated['fnamattdfakturpenjualan2'] ?? null,
            'fnamattdpo' => $validated['fnamattdpo'] ?? '',
            'fnamattdpo2' => $validated['fnamattdpo2'] ?? null,
        ];

        if (isset($validated['fppntarif'])) {
            $updateData['fppntarif'] = $validated['fppntarif'];
        }

        DB::table('setini')->update($updateData);

        return redirect()
            ->route('systemsetting.edit')
            ->with('success', 'System Setting berhasil disimpan.');
    }
}
