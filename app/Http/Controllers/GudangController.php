<?php

namespace App\Http\Controllers;

use App\Models\Gudang;    
use App\Models\Cabang;    
use Illuminate\Http\Request;

class GudangController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fgudangcode', 'fgudangid', 'fgudangname'])
            ? $request->filter_by
            : 'fgudangcode';

        $search = $request->search;

        $gudangs = Gudang::with('cabang') // Eager load the cabang relationship
            ->when($search, function($q) use ($filterBy, $search) {
                $q->where($filterBy, 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('fgudangid', 'desc')
            ->paginate(10)
            ->withQueryString(); 

        return view('gudang.index', compact('gudangs', 'filterBy', 'search'));
    }
    
    public function create()
    {
        // Fetch all cabang records for the dropdown
        $cabangOptions = Cabang::all(); // Assuming you have a Cabang model

        // Return the create view with the cabangOptions data
        return view('gudang.create', compact('cabangOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fgudangcode' => 'required|string|unique:msgudang,fgudangcode',
            'fgudangname' => 'required|string',
            'faddress' => 'required|string',
            'fcabangkode' => 'required|string', // Ensure the cabang code is validated and passed
        ],
        [
            'fgudangcode.unique' => 'Kode Gudang sudah ada.',
            'fgudangcode.required' => 'Kode Gudang harus diisi.',
            'fgudangname.required' => 'Nama Gudang harus diisi.',
            'faddress.required' => 'Alamat Gudang harus diisi.',
            'fcabangkode.required' => 'Kode Cabang harus dipilih.',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = '0';

        // Create the new Gudang, including the `fcabangkode` field
        Gudang::create($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function edit($fgudangid)
    {
        // Fetch the Gudang record by ID
        $gudang = Gudang::findOrFail($fgudangid);

        // Fetch all cabang records for the dropdown
        $cabangOptions = Cabang::all(); // Assuming you have a Cabang model

        // Return the edit view with the Gudang data and cabangOptions
        return view('gudang.edit', compact('gudang', 'cabangOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fgudangid)
    {
        // Validasi
        $validated = $request->validate([
            'fgudangcode' => "required|string|unique:msgudang,fgudangcode,{$fgudangid},fgudangid",
            'fgudangname' => 'required|string',
            'faddress' => 'required|string',
            'fcabangkode' => 'required|string', // Ensure the cabang code is validated and passed
        ],
        [
            'fgudangcode.unique' => 'Kode Gudang sudah ada.',
            'fgudangcode.required' => 'Kode Gudang harus diisi.',
            'fgudangname.required' => 'Nama Gudang harus diisi.',
            'faddress.required' => 'Alamat Gudang harus diisi.',
            'fcabangkode.required' => 'Kode Cabang harus dipilih.',
        ]);

        $validated['fnonactive'] = '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        $gudang = Gudang::findOrFail($fgudangid);
        $gudang->update($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil di-update.');
    }

    public function destroy($fgudangid)
    {
        $gudang = Gudang::findOrFail($fgudangid);
        $gudang->delete();

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil dihapus.');
    }
}
