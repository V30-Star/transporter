<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\TypePembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->orderBy('ftypepembayarankode', 'asc')
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
                'ftypepembayarankode' => strtoupper(trim((string) $request->ftypepembayarankode)),
                'ftypepembayaranname' => strtoupper(trim((string) $request->ftypepembayaranname)),
                'faccount' => trim((string) $request->faccount),
            ]);

            $validated = $request->validate([
                'ftypepembayarankode' => 'required|string|max:10|unique:msttypepembayaran,ftypepembayarankode',
                'ftypepembayaranname' => 'required|string|max:50',
                'faccount' => 'required|string|max:10',
            ], [
                'ftypepembayarankode.required' => 'Kode Type Pembayaran wajib diisi.',
                'ftypepembayarankode.unique' => 'Kode Type Pembayaran sudah digunakan.',
                'ftypepembayarankode.max' => 'Kode Type Pembayaran maksimal 10 karakter.',
                'ftypepembayaranname.required' => 'Nama Type Pembayaran wajib diisi.',
                'ftypepembayaranname.max' => 'Nama Type Pembayaran maksimal 50 karakter.',
                'faccount.required' => 'Account Kas/Bank wajib dipilih.',
            ]);

            $userLogin = auth('sysuser')->user() ?? auth()->user();
            $validated['fcreateby'] = $userLogin->fname ?? ($userLogin->name ?? null);
            $validated['fcreatedat'] = now();

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

        $typePembayaran = TypePembayaran::findOrFail($id);
        $accounts = $this->getKasbankAccounts();

        return view('typepembayaran.edit', compact('typePembayaran', 'accounts'));
    }

    public function view($id)
    {
        if ($guard = $this->ensurePermission('viewTypePembayaran')) {
            return $guard;
        }

        $typePembayaran = TypePembayaran::with('account')->findOrFail($id);

        return view('typepembayaran.view', compact('typePembayaran'));
    }

    public function update(Request $request, $id)
    {
        if ($guard = $this->ensurePermission('updateTypePembayaran')) {
            return $guard;
        }

        $typePembayaran = TypePembayaran::findOrFail($id);

        $request->merge([
            'ftypepembayarankode' => strtoupper(trim((string) $request->ftypepembayarankode)),
            'ftypepembayaranname' => strtoupper(trim((string) $request->ftypepembayaranname)),
            'faccount' => trim((string) $request->faccount),
        ]);

        $validated = $request->validate([
            'ftypepembayarankode' => "required|string|max:10|unique:msttypepembayaran,ftypepembayarankode,{$id},ftypepembayaranid",
            'ftypepembayaranname' => 'required|string|max:50',
            'faccount' => 'required|string|max:10',
        ], [
            'ftypepembayarankode.required' => 'Kode Type Pembayaran wajib diisi.',
            'ftypepembayarankode.unique' => 'Kode Type Pembayaran sudah digunakan.',
            'ftypepembayarankode.max' => 'Kode Type Pembayaran maksimal 10 karakter.',
            'ftypepembayaranname.required' => 'Nama Type Pembayaran wajib diisi.',
            'ftypepembayaranname.max' => 'Nama Type Pembayaran maksimal 50 karakter.',
            'faccount.required' => 'Account Kas/Bank wajib dipilih.',
        ]);

        $userLogin = auth('sysuser')->user() ?? auth()->user();
        $validated['fupdatedby'] = $userLogin->fname ?? ($userLogin->name ?? null);
        $validated['fupdatedat'] = now();

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

        $typePembayaran = TypePembayaran::with('account')->findOrFail($id);

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
            $typePembayaran = TypePembayaran::findOrFail($id);
            $typePembayaran->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Type Pembayaran ' . $typePembayaran->ftypepembayaranname . ' berhasil dihapus.',
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
