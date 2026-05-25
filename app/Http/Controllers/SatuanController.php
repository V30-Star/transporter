<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    private function ensureSatuanPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu satuan.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureSatuanPermission('viewSatuan')) {
            return $guard;
        }

        $satuans = Satuan::orderBy('fsatuancode', 'asc')
            ->get(['fsatuanid', 'fsatuancode', 'fsatuanname', 'fnonactive']);

        $permsStr = (string) session('user_restricted_permissions', '');
        $permsArr = explode(',', $permsStr);
        $canCreate = in_array('createSatuan', $permsArr, true);
        $canEdit = in_array('updateSatuan', $permsArr, true);
        $canDelete = in_array('deleteSatuan', $permsArr, true);

        return view('satuan.index', compact('satuans', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureSatuanPermission('createSatuan')) {
            return $guard;
        }

        return view('satuan.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureSatuanPermission('createSatuan')) {
            return $guard;
        }

        $request->merge([
            'fsatuancode' => strtoupper($request->fsatuancode),
            'fsatuanname' => strtoupper($request->fsatuanname),
        ]);

        $validated = $request->validate(
            [
                'fsatuancode' => 'required|string|unique:mssatuan,fsatuancode',
                'fsatuanname' => 'required|string',
            ],
            [
                'fsatuancode.unique' => 'Kode satuan sudah ada.',
                'fsatuanname.unique' => 'Nama satuan sudah ada.',
                'fsatuancode.required' => 'Kode satuan wajib diisi.',
            ]
        );

        $validated['fsatuancode'] = strtoupper($validated['fsatuancode']);
        $validated['fsatuanname'] = strtoupper($validated['fsatuanname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Satuan
        Satuan::create($validated);

        return redirect()
            ->route('satuan.create')
            ->with('success', 'Satuan berhasil disimpan.');
    }

    public function edit($fsatuanid)
    {
        if ($guard = $this->ensureSatuanPermission('updateSatuan')) {
            return $guard;
        }

        $satuan = Satuan::findOrFail($fsatuanid);

        return view('satuan.edit', [
            'satuan' => $satuan,
            'action' => 'edit',
        ]);
    }

    public function view($fsatuanid)
    {
        if ($guard = $this->ensureSatuanPermission('viewSatuan')) {
            return $guard;
        }

        $satuan = Satuan::findOrFail($fsatuanid);

        return view('satuan.view', [
            'satuan' => $satuan,
        ]);
    }

    public function update(Request $request, $fsatuanid)
    {
        if ($guard = $this->ensureSatuanPermission('updateSatuan')) {
            return $guard;
        }

        $validated = $request->validate(
            [
                'fsatuancode' => "required|string|unique:mssatuan,fsatuancode,{$fsatuanid},fsatuanid",
                'fsatuanname' => 'required|string',
            ],
            [
                'fsatuancode.unique' => 'Kode satuan sudah ada.',
                'fsatuancode.required' => 'Kode satuan wajib diisi.',
            ]
        );

        $validated['fsatuancode'] = strtoupper($validated['fsatuancode']);
        $validated['fsatuanname'] = strtoupper($validated['fsatuanname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $satuan = Satuan::findOrFail($fsatuanid);
        $satuan->update($validated);

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil diupdate.');
    }

    public function delete($fsatuanid)
    {
        if ($guard = $this->ensureSatuanPermission('deleteSatuan')) {
            return $guard;
        }

        $satuan = Satuan::findOrFail($fsatuanid);

        return view('satuan.delete', [
            'satuan' => $satuan,
        ]);
    }

    public function destroy($fsatuanid)
    {
        if (! $this->hasRestrictedPermission('deleteSatuan')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu satuan.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu satuan.');
        }

        try {
            $satuan = Satuan::findOrFail($fsatuanid);

            if (\Illuminate\Support\Facades\DB::table('msprd')
                ->where('fsatuankecil', $satuan->fsatuancode)
                ->orWhere('fsatuanbesar', $satuan->fsatuancode)
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Satuan tidak bisa dihapus. Sudah direferensi di produk.',
                ], 422);
            }

            $satuan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Satuan '.$satuan->fsatuanname.' berhasil dihapus.',
                'redirect' => route('satuan.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }
}
