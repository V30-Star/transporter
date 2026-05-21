<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        $rekenings = Rekening::orderBy('frekeningname', 'asc')
            ->get(['frekeningcode', 'frekeningname', 'frekeningid', 'fnonactive']);

        $canCreate = in_array('createRekening', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateRekening', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteRekening', explode(',', session('user_restricted_permissions', '')));

        return view('rekening.index', compact('rekenings', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('rekening.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'frekeningname' => strtoupper($request->frekeningname),
        ]);

        $validated = $request->validate(
            [
                'frekeningname' => 'required|string|unique:msrekening,frekeningname',
            ],
            [
                'frekeningname.required' => 'NAMA REKENING WAJIB DIISI.',
                'frekeningname.unique' => 'NAMA REKENING SUDAH ADA.',
            ]
        );

        $validated['frekeningname'] = strtoupper($validated['frekeningname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Rekening::create($validated);

        return redirect()
            ->route('rekening.create')
            ->with('success', 'REKENING BERHASIL DISIMPAN.');
    }

    public function edit($frekeningid)
    {
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
        $rekening = Rekening::findOrFail($frekeningid);

        return view('rekening.view', [
            'rekening' => $rekening,
        ]);
    }

    public function update(Request $request, $frekeningid)
    {
        $request->merge([
            'frekeningname' => strtoupper($request->frekeningname),
        ]);

        $validated = $request->validate(
            [
                'frekeningname' => 'required|string|string|unique:msrekening,frekeningname',
            ],
            [
                'frekeningname.required' => 'NAMA REKENING WAJIB DIISI.',
                'frekeningname.unique' => 'NAMA REKENING SUDAH ADA.',
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
            ->with('success', 'REKENING BERHASIL DIUPDATE.');
    }

    public function delete($frekeningid)
    {
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
                'message' => 'REKENING '.$rekening->frekeningname.' BERHASIL DIHAPUS.',
                'redirect' => route('rekening.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'REKENING BELUM BISA DIHAPUS. COBA LAGI.',
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

        return 'REKENING ' . strtoupper((string) $rekening->frekeningname) . ' TIDAK BISA DIHAPUS. SUDAH DIREFERENSI DI TRANSAKSI.';
    }
}
