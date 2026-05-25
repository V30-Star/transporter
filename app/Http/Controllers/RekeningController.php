<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekeningController extends Controller
{
    private function ensureRekeningPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu rekening.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureRekeningPermission('viewRekening')) {
            return $guard;
        }

        $rekenings = Rekening::orderBy('frekeningname', 'asc')
            ->get(['frekeningcode', 'frekeningname', 'frekeningid', 'fnonactive']);

        $canCreate = in_array('createRekening', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateRekening', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteRekening', explode(',', session('user_restricted_permissions', '')));

        return view('rekening.index', compact('rekenings', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureRekeningPermission('createRekening')) {
            return $guard;
        }

        return view('rekening.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureRekeningPermission('createRekening')) {
            return $guard;
        }

        $request->merge([
            'frekeningname' => strtoupper($request->frekeningname),
        ]);

        $validated = $request->validate(
            [
                'frekeningname' => 'required|string|unique:msrekening,frekeningname',
            ],
            [
                'frekeningname.required' => 'Nama rekening wajib diisi.',
                'frekeningname.unique' => 'Nama rekening sudah ada.',
            ]
        );

        $validated['frekeningname'] = strtoupper($validated['frekeningname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Rekening::create($validated);

        return redirect()
            ->route('rekening.create')
            ->with('success', 'Rekening berhasil disimpan.');
    }

    public function edit($frekeningid)
    {
        if ($guard = $this->ensureRekeningPermission('updateRekening')) {
            return $guard;
        }

        $rekening = Rekening::findOrFail($frekeningid);
        $isTransactionLocked = $this->hasTransactionUsage($rekening);

        return view('rekening.edit', [
            'rekening' => $rekening,
            'isTransactionLocked' => $isTransactionLocked,
            'action' => 'edit',
        ]);
    }

    public function view($frekeningid)
    {
        if ($guard = $this->ensureRekeningPermission('viewRekening')) {
            return $guard;
        }

        $rekening = Rekening::findOrFail($frekeningid);

        return view('rekening.view', [
            'rekening' => $rekening,
        ]);
    }

    public function update(Request $request, $frekeningid)
    {
        if ($guard = $this->ensureRekeningPermission('updateRekening')) {
            return $guard;
        }

        $request->merge([
            'frekeningname' => strtoupper($request->frekeningname),
        ]);

        $validated = $request->validate(
            [
                'frekeningname' => 'required|string|string|unique:msrekening,frekeningname',
            ],
            [
                'frekeningname.required' => 'Nama rekening wajib diisi.',
                'frekeningname.unique' => 'Nama rekening sudah ada.',
            ]
        );

        $validated['frekeningname'] = strtoupper($validated['frekeningname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['frekeningcode'] = '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $rekening = Rekening::findOrFail($frekeningid);
        $rekening->update($validated);

        return redirect()
            ->route('rekening.index')
            ->with('success', 'Rekening berhasil diupdate.');
    }

    public function delete($frekeningid)
    {
        if ($guard = $this->ensureRekeningPermission('deleteRekening')) {
            return $guard;
        }

        $rekening = Rekening::findOrFail($frekeningid);

        if ($message = $this->getUsageLockMessage($rekening)) {
            return redirect()->route('rekening.view', $rekening->frekeningid)->with('error', $message);
        }

        return view('rekening.delete', [
            'rekening' => $rekening,
        ]);
    }

    public function destroy($frekeningid)
    {
        if (! $this->hasRestrictedPermission('deleteRekening')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu rekening.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu rekening.');
        }

        try {
            $rekening = Rekening::findOrFail($frekeningid);

            if ($message = $this->getUsageLockMessage($rekening)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('rekening.view', $rekening->frekeningid),
                    ], 422);
                }

                return redirect()->route('rekening.view', $rekening->frekeningid)->with('error', $message);
            }

            $rekening->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rekening '.$rekening->frekeningname.' berhasil dihapus.',
                'redirect' => route('rekening.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    private function hasTransactionUsage(Rekening $rekening): bool
    {
        return DB::table('mscustomer')->where('frekening', $rekening->frekeningid)->exists();
    }

    private function getUsageLockMessage(Rekening $rekening): ?string
    {
        if (! $this->hasTransactionUsage($rekening)) {
            return null;
        }

        return 'Rekening ' . strtoupper((string) $rekening->frekeningname) . ' tidak bisa dihapus. Sudah direferensi di transaksi.';
    }
}
