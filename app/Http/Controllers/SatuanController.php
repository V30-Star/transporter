<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index(Request $request)
    {
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
        return view('satuan.create');
    }

    public function store(Request $request)
    {
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
                'fsatuancode.unique' => 'KODE SATUAN SUDAH ADA.',
                'fsatuanname.unique' => 'NAMA SATUAN SUDAH ADA.',
                'fsatuancode.required' => 'KODE SATUAN WAJIB DIISI.',
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
            ->with('success', 'SATUAN BERHASIL DISIMPAN.');
    }

    public function edit($fsatuanid)
    {
        $satuan = Satuan::findOrFail($fsatuanid);

        return view('satuan.edit', [
            'satuan' => $satuan,
            'action' => 'edit',
        ]);
    }

    public function update(Request $request, $fsatuanid)
    {
        $validated = $request->validate(
            [
                'fsatuancode' => "required|string|unique:mssatuan,fsatuancode,{$fsatuanid},fsatuanid",
                'fsatuanname' => 'required|string',
            ],
            [
                'fsatuancode.unique' => 'KODE SATUAN SUDAH ADA.',
                'fsatuancode.required' => 'KODE SATUAN WAJIB DIISI.',
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
            ->with('success', 'SATUAN BERHASIL DIUPDATE.');
    }

    public function delete($fsatuanid)
    {
        $satuan = Satuan::findOrFail($fsatuanid);

        return view('satuan.delete', [
            'satuan' => $satuan,
        ]);
    }

    public function destroy($fsatuanid)
    {
        try {
            $satuan = Satuan::findOrFail($fsatuanid);

            if (\Illuminate\Support\Facades\DB::table('msprd')
                ->where('fsatuankecil', $satuan->fsatuancode)
                ->orWhere('fsatuanbesar', $satuan->fsatuancode)
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'SATUAN TIDAK BISA DIHAPUS. SUDAH DIREFERENSI DI PRODUK.',
                ], 422);
            }

            $satuan->delete();

            return response()->json([
                'success' => true,
                'message' => 'SATUAN '.$satuan->fsatuanname.' BERHASIL DIHAPUS.',
                'redirect' => route('satuan.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SATUAN BELUM BISA DIHAPUS. COBA LAGI.',
            ], 500);
        }
    }
}
