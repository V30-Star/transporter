<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PenamaanPerusahaanController extends Controller
{
    private function checkPasswordMatch(?string $input, ?string $stored): bool
    {
        // Belum ada password tersimpan: tidak ada password bawaan. Akses dibuka lewat isAuthorized().
        if (empty($stored)) {
            return false;
        }

        // 1. Cek Crypt Decrypt (AES-256)
        $decrypted = decrypt_value($stored);
        if ($decrypted !== '' && $decrypted === $input) {
            return true;
        }

        // 2. Cek Plain Text
        if ($stored === $input) {
            return true;
        }

        // 3. Cek Bcrypt Hash (dengan try-catch agar aman jika bukan hash)
        try {
            if (Hash::check($input, $stored)) {
                return true;
            }
        } catch (\Throwable $e) {
            // Bukan format bcrypt
        }

        return false;
    }

    /** Sesi sudah diverifikasi, atau memang belum ada password perusahaan yang diatur (pengaturan awal). */
    private function isAuthorized(): bool
    {
        return (bool) session('penamaan_perusahaan_auth', false)
            || empty(DB::table('setini')->value('fpasswordperusahaan'));
    }

    private function verifyThrottleKey(Request $request): string
    {
        return 'penamaan-verify:' . (auth('sysuser')->id() ?? 'guest') . '|' . $request->ip();
    }

    public function index(Request $request)
    {
        if (!$this->isAuthorized()) {
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

        $throttleKey = $this->verifyThrottleKey($request);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withInput()->with('error', "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.");
        }

        $stored = DB::table('setini')->value('fpasswordperusahaan');

        if (!$this->checkPasswordMatch($request->password, $stored)) {
            RateLimiter::hit($throttleKey, 300);

            return back()->withInput()->with('error', 'Password salah! Akses ditolak.');
        }

        RateLimiter::clear($throttleKey);
        session(['penamaan_perusahaan_auth' => true]);

        return redirect()->route('penamaanperusahaan.index')
            ->with('success', 'Akses berhasil diverifikasi.');
    }

    public function update(Request $request)
    {
        if (!$this->isAuthorized()) {
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
