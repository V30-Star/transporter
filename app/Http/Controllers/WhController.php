<?php

namespace App\Http\Controllers;

use App\Models\Wh;
use App\Models\Cabang;
use Illuminate\Http\Request;

class WhController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fwhcode', 'fwhname', 'fwhid', 'faddress'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fwhid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $permsArr  = explode(',', (string) session('user_restricted_permissions', ''));
        $canCreate = in_array('createGudang', $permsArr, true);
        $canEdit   = in_array('updateGudang', $permsArr, true);
        $canDelete = in_array('deleteGudang', $permsArr, true);

        $gudangs = Wh::orderBy($sortBy, $sortDir)->get(['fwhcode', 'fwhname', 'fwhid', 'faddress']);

        return view('gudang.index', compact('gudangs', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.create', compact('cabangOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fwhcode' => 'required|string|unique:mswh,fwhcode',
                'fwhname' => 'required|string',
                'faddress' => 'required|string',
                'fbranchcode' => 'required|string',
            ],
            [
                'fwhcode.unique' => 'Kode Gudang sudah ada.',
                'fwhcode.required' => 'Kode Gudang harus diisi.',
                'fwhname.required' => 'Nama Gudang harus diisi.',
                'faddress.required' => 'Alamat Gudang harus diisi.',
                'fbranchcode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Wh::create($validated);

        return redirect()
            ->route('gudang.create')
            ->with('success', 'Wh berhasil ditambahkan.');
    }

    public function edit($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);

        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.edit', compact('gudang', 'cabangOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fwhid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fwhcode' => "required|string|unique:mswh,fwhcode,{$fwhid},fwhid",
                'fwhname' => 'required|string',
                'faddress' => 'required|string',
                'fbranchcode' => 'required|string', // Ensure the cabang code is validated and passed
            ],
            [
                'fwhcode.unique' => 'Kode Wh sudah ada.',
                'fwhcode.required' => 'Kode Wh harus diisi.',
                'fwhname.required' => 'Nama Wh harus diisi.',
                'faddress.required' => 'Alamat Wh harus diisi.',
                'fbranchcode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        $gudang = Wh::findOrFail($fwhid);
        $gudang->update($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Wh berhasil di-update.');
    }

    public function destroy($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);
        $gudang->delete();

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Wh berhasil dihapus.');
    }
}
