<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fsatuancode', 'fsatuanname', 'fsatuanid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsatuanid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $satuans = Satuan::orderBy($sortBy, $sortDir)->get(['fsatuanid', 'fsatuancode', 'fsatuanname']);

        $permsStr  = (string) session('user_restricted_permissions', '');
        $permsArr  = explode(',', $permsStr);
        $canCreate = in_array('createSatuan', $permsArr, true);
        $canEdit   = in_array('updateSatuan', $permsArr, true);
        $canDelete = in_array('deleteSatuan', $permsArr, true);

        return view('satuan.index', compact('satuans', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('satuan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fsatuancode' => 'required|string|unique:mssatuan,fsatuancode',
                'fsatuanname' => 'required|string',
            ],
            [
                'fsatuancode.unique' => 'Kode Satuan sudah ada.',
                'fsatuancode.required' => 'Kode Satuan harus diisi.',
                'fsatuanname.required' => 'Nama Satuan harus diisi.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Satuan
        Satuan::create($validated);

        return redirect()
            ->route('satuan.create')
            ->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function edit($fsatuanid)
    {
        // Ambil data berdasarkan PK fsatuanid
        $satuan = Satuan::findOrFail($fsatuanid);

        return view('satuan.edit', compact('satuan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fsatuanid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fsatuancode' => "required|string|unique:mssatuan,fsatuancode,{$fsatuanid},fsatuanid",
                'fsatuanname' => 'required|string',
            ],
            [
                'fsatuancode.unique' => 'Kode Satuan sudah ada.',
                'fsatuancode.required' => 'Kode Satuan harus diisi.',
                'fsatuanname.required' => 'Nama Satuan harus diisi.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        // Cari dan update
        $satuan = Satuan::findOrFail($fsatuanid);
        $satuan->update($validated);

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil di-update.');
    }

    public function destroy($fsatuanid)
    {
        $satuan = Satuan::findOrFail($fsatuanid);
        $satuan->delete();

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil dihapus.');
    }
}
