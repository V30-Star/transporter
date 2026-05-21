<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    // protected $permission = [];

    public function __construct()
    {
        $restrictedPermissions = session('user_restricted_permissions', []);

        $this->restrictedPermission = $restrictedPermissions ? explode(',', $restrictedPermissions) : [];
    }

    public function index(Request $request)
    {
        $wilayahs = Wilayah::orderBy('fwilayahcode', 'asc')
            ->get(['fwilayahcode', 'fwilayahname', 'fwilayahid', 'fnonactive']);

        $canCreate = in_array('createWilayah', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateWilayah', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteWilayah', explode(',', session('user_restricted_permissions', '')));

        return view('wilayah.index', compact('wilayahs', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('wilayah.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fwilayahcode' => strtoupper($request->fwilayahcode),
        ]);

        $validated = $request->validate([
            'fwilayahcode' => 'required|string|unique:mswilayah,fwilayahcode',
            'fwilayahname' => 'required|string',
        ], [
            'fwilayahcode.required' => 'Kode wilayah wajib diisi.',
            'fwilayahname.required' => 'Nama wilayah wajib diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada.',
        ]);

        // Add default values for the required fields
        $validated['fwilayahcode'] = strtoupper($validated['fwilayahcode']);
        $validated['fwilayahname'] = strtoupper($validated['fwilayahname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Wilayah::create($validated);

        return redirect()
            ->route('wilayah.create')
            ->with('success', 'Wilayah berhasil disimpan.');
    }

    public function edit($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $isTransactionLocked = $this->hasTransactionUsage($wilayah);

        return view('wilayah.edit', [
            'wilayah' => $wilayah,
            'isTransactionLocked' => $isTransactionLocked,
            'action' => 'edit',
        ]);
    }

    public function view($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);

        return view('wilayah.view', [
            'wilayah' => $wilayah,
        ]);
    }

    public function update(Request $request, $fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $isTransactionLocked = $this->hasTransactionUsage($wilayah);

        $request->merge([
            'fwilayahcode' => strtoupper($isTransactionLocked ? $wilayah->fwilayahcode : $request->fwilayahcode),
        ]);

        $validated = $request->validate([
            'fwilayahcode' => "required|string|unique:mswilayah,fwilayahcode,{$fwilayahid},fwilayahid",
            'fwilayahname' => 'required|string',
        ], [
            'fwilayahcode.required' => 'Kode wilayah wajib diisi.',
            'fwilayahname.required' => 'Nama wilayah wajib diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada.',
        ]);

        // Add default values for the required fields
        $validated['fwilayahcode'] = strtoupper($validated['fwilayahcode']);
        $validated['fwilayahname'] = strtoupper($validated['fwilayahname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        if ($isTransactionLocked) {
            $validated['fwilayahcode'] = $wilayah->fwilayahcode;
        }

        $wilayah->update($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil diupdate.');
    }

    public function delete($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);

        if ($message = $this->getUsageLockMessage($wilayah)) {
            return redirect()->route('wilayah.view', $wilayah->fwilayahid)->with('error', $message);
        }

        return view('wilayah.delete', [
            'wilayah' => $wilayah,
        ]);
    }

    public function destroy($fwilayahid)
    {
        try {
            $wilayah = Wilayah::findOrFail($fwilayahid);

            if ($message = $this->getUsageLockMessage($wilayah)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('wilayah.view', $wilayah->fwilayahid),
                    ], 422);
                }

                return redirect()->route('wilayah.view', $wilayah->fwilayahid)->with('error', $message);
            }

            $wilayah->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wilayah '.$wilayah->fwilayahname.' berhasil dihapus.',
                'redirect' => route('wilayah.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wilayah belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    private function hasTransactionUsage(Wilayah $wilayah): bool
    {
        return DB::table('mscustomer')->where('fwilayah', $wilayah->fwilayahid)->exists();
    }

    private function getUsageLockMessage(Wilayah $wilayah): ?string
    {
        if (! $this->hasTransactionUsage($wilayah)) {
            return null;
        }

        return 'Wilayah ' . strtoupper((string) $wilayah->fwilayahcode) . ' tidak bisa dihapus. Sudah direferensi di transaksi.';
    }
}
