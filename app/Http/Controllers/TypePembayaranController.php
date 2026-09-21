<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\TypePembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TypePembayaranController extends Controller
{
    private function ensurePermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu Type Pembayaran.');
    }

    private function getKasbankAccounts()
    {
        $kasbankHeader = DB::table('set_account')->where('faccount_name', 'KASBANKHEADER')->value('faccount') ?? '11100';

        return Account::query()
            ->where('fend', '1')
            ->where('fnonactive', '0')
            ->when($kasbankHeader, function ($q) use ($kasbankHeader) {
                $q->where('faccupline', $kasbankHeader);
            })
            ->orderBy('faccount', 'asc')
            ->get(['faccid', 'faccount', 'faccname']);
    }

    public function index()
    {
        if ($guard = $this->ensurePermission('viewTypePembayaran')) {
            return $guard;
        }

        $typePembayarans = TypePembayaran::with('account')
            ->where('ftblcode', 'TYPEBAYAR')
            ->orderBy('fmasternum', 'asc')
            ->orderBy('fmastername', 'asc')
            ->get();

        $permsArr = explode(',', (string) session('user_restricted_permissions', ''));
        $canCreate = in_array('createTypePembayaran', $permsArr, true);
        $canEdit = in_array('updateTypePembayaran', $permsArr, true);
        $canDelete = in_array('deleteTypePembayaran', $permsArr, true);

        return view('typepembayaran.index', compact('typePembayarans', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensurePermission('createTypePembayaran')) {
            return $guard;
        }

        $accounts = $this->getKasbankAccounts();

        return view('typepembayaran.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensurePermission('createTypePembayaran')) {
            return $guard;
        }

        try {
            $request->merge([
                'ftblcode' => 'TYPEBAYAR',
                'fmasternum' => $request->filled('fmasternum') ? (int) $request->fmasternum : null,
                'fnumvalue' => $request->filled('fnumvalue') ? (float) str_replace(',', '.', (string) $request->fnumvalue) : 0,
                'fmastername' => strtoupper(trim((string) $request->fmastername)),
                'fnote1' => trim((string) ($request->fnote1 ?? $request->faccount)),
            ]);

            $validated = $request->validate([
                'ftblcode' => 'required|string',
                'fmasternum' => 'nullable|integer|min:0',
                'fnumvalue' => 'nullable|numeric|min:0|max:100',
                'fmastername' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tbmaster', 'fmastername')->where(fn($q) => $q->where('ftblcode', 'TYPEBAYAR')),
                ],
                'fnote1' => 'required|string|max:10',
            ], [
                'fmasternum.integer' => 'No. Urut harus berupa angka.',
                'fnumvalue.numeric' => 'Biaya/Charge (%) harus berupa angka.',
                'fmastername.required' => 'Nama Type Pembayaran wajib diisi.',
                'fmastername.unique' => 'Nama Type Pembayaran sudah digunakan.',
                'fmastername.max' => 'Nama Type Pembayaran maksimal 50 karakter.',
                'fnote1.required' => 'Account wajib dipilih.',
            ]);

            $userLogin = auth('sysuser')->user() ?? auth()->user();
            $validated['fuserid'] = $userLogin->fname ?? ($userLogin->name ?? 'System');
            $validated['fdatetime'] = now();

            TypePembayaran::create($validated);

            return redirect()
                ->route('typepembayaran.create')
                ->with('success', 'Type Pembayaran berhasil disimpan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan Type Pembayaran: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        if ($guard = $this->ensurePermission('updateTypePembayaran')) {
            return $guard;
        }

        $typePembayaran = TypePembayaran::where('ftblcode', 'TYPEBAYAR')->findOrFail($id);
        $accounts = $this->getKasbankAccounts();

        return view('typepembayaran.edit', compact('typePembayaran', 'accounts'));
    }

    public function view($id)
    {
        if ($guard = $this->ensurePermission('viewTypePembayaran')) {
            return $guard;
        }

        $typePembayaran = TypePembayaran::with('account')->where('ftblcode', 'TYPEBAYAR')->findOrFail($id);

        return view('typepembayaran.view', compact('typePembayaran'));
    }

    public function update(Request $request, $id)
    {
        if ($guard = $this->ensurePermission('updateTypePembayaran')) {
            return $guard;
        }

        $typePembayaran = TypePembayaran::where('ftblcode', 'TYPEBAYAR')->findOrFail($id);

        $request->merge([
            'ftblcode' => 'TYPEBAYAR',
            'fmasternum' => $request->filled('fmasternum') ? (int) $request->fmasternum : null,
            'fnumvalue' => $request->filled('fnumvalue') ? (float) str_replace(',', '.', (string) $request->fnumvalue) : 0,
            'fmastername' => strtoupper(trim((string) $request->fmastername)),
            'fnote1' => trim((string) ($request->fnote1 ?? $request->faccount)),
        ]);

        $validated = $request->validate([
            'ftblcode' => 'required|string',
            'fmasternum' => 'nullable|integer|min:0',
            'fnumvalue' => 'nullable|numeric|min:0|max:100',
            'fmastername' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tbmaster', 'fmastername')
                    ->where(fn($q) => $q->where('ftblcode', 'TYPEBAYAR'))
                    ->ignore($id, 'fmasterid'),
            ],
            'fnote1' => 'required|string|max:10',
        ], [
            'fmasternum.integer' => 'No. Urut harus berupa angka.',
            'fnumvalue.numeric' => 'Biaya/Charge (%) harus berupa angka.',
            'fmastername.required' => 'Nama Type Pembayaran wajib diisi.',
            'fmastername.unique' => 'Nama Type Pembayaran sudah digunakan.',
            'fmastername.max' => 'Nama Type Pembayaran maksimal 50 karakter.',
            'fnote1.required' => 'Account wajib dipilih.',
        ]);

        $userLogin = auth('sysuser')->user() ?? auth()->user();
        $validated['fuserid'] = $userLogin->fname ?? ($userLogin->name ?? 'System');
        $validated['fdatetime'] = now();

        $typePembayaran->update($validated);

        return redirect()
            ->route('typepembayaran.index')
            ->with('success', 'Type Pembayaran berhasil diupdate.');
    }

    public function delete($id)
    {
        if ($guard = $this->ensurePermission('deleteTypePembayaran')) {
            return $guard;
        }

        $typePembayaran = TypePembayaran::with('account')->where('ftblcode', 'TYPEBAYAR')->findOrFail($id);

        return view('typepembayaran.delete', compact('typePembayaran'));
    }

    public function destroy($id)
    {
        if (! $this->hasRestrictedPermission('deleteTypePembayaran')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus Type Pembayaran.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses untuk menghapus Type Pembayaran.');
        }

        try {
            $typePembayaran = TypePembayaran::where('ftblcode', 'TYPEBAYAR')->findOrFail($id);
            $typePembayaran->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Type Pembayaran ' . $typePembayaran->fmastername . ' berhasil dihapus.',
                    'redirect' => route('typepembayaran.index'),
                ]);
            }

            return redirect()
                ->route('typepembayaran.index')
                ->with('success', 'Type Pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type Pembayaran gagal dihapus: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('typepembayaran.index')
                ->with('error', 'Gagal menghapus Type Pembayaran: ' . $e->getMessage());
        }
    }
}
