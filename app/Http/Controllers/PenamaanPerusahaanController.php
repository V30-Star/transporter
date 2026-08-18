<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PenamaanPerusahaanController extends Controller
{
    private function checkPasswordMatch(?string $input, ?string $stored): bool
    {
        if (empty($stored)) {
            // Default password jika belum pernah diset di database
            return $input === 'admin1234';
        }

        // Cek Hash
        if (Hash::check($input, $stored)) {
            return true;
        }

        // Cek Crypt Decrypt
        try {
            if (Crypt::decryptString($stored) === $input) {
                return true;
            }
        } catch (\Throwable $e) {
            // fallback plain text
        }

        // Cek Plain Text
        return $stored === $input;
    }

    public function index(Request $request)
    {
        $isAuth = session('penamaan_perusahaan_auth', false);

        if (!$isAuth) {
            return view('penamaanperusahaan.auth', [
                'pageTitle' => 'Verifikasi Akses Penamaan Perusahaan',
            ]);
        }

        $setini = DB::table('setini')->first();
        $projectName = decrypt_value($setini->fproject ?? '');

        return view('penamaanperusahaan.edit', [
            'pageTitle' => 'Penamaan Perusahaan',
            'projectName' => $projectName,
            'hasPassword' => !empty($setini->fpasswordperusahaan),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ], [
            'password.required' => 'Password wajib diisi.',
        ]);

        $stored = DB::table('setini')->value('fpasswordperusahaan');

        if (!$this->checkPasswordMatch($request->password, $stored)) {
            return back()->withInput()->with('error', 'Password salah! Akses ditolak.');
        }

        session(['penamaan_perusahaan_auth' => true]);

        return redirect()->route('penamaanperusahaan.index')
            ->with('success', 'Akses berhasil diverifikasi.');
    }

    public function update(Request $request)
    {
        if (!session('penamaan_perusahaan_auth', false)) {
            return redirect()->route('penamaanperusahaan.index')
                ->with('error', 'Silakan verifikasi password terlebih dahulu.');
        }

        $request->validate([
            'fproject' => 'required|string|max:200',
            'new_password' => 'nullable|string|min:4|confirmed',
        ], [
            'fproject.required' => 'Header Faktur / Nama Perusahaan wajib diisi.',
            'new_password.min' => 'Password baru minimal 4 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $projectRaw = trim($request->fproject);
        $updateData = [
            'fproject' => $projectRaw !== '' ? Crypt::encryptString($projectRaw) : '',
        ];

        if (!empty($request->new_password)) {
            $updateData['fpasswordperusahaan'] = Crypt::encryptString($request->new_password);
        }

        DB::table('setini')->update($updateData);

        return redirect()->route('penamaanperusahaan.index')
            ->with('success', 'Nama Perusahaan berhasil disimpan.');
    }

    public function lock()
    {
        session()->forget('penamaan_perusahaan_auth');

        return redirect()->route('dashboard')
            ->with('success', 'Sesi Penamaan Perusahaan telah dikunci.');
    }
}
