<?php

namespace App\Http\Controllers;
use App\Models\Wilayah;    
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fwilayahcode', 'fwilayahname'])
            ? $request->filter_by
            : 'fwilayahcode';

        $search = $request->search;

        $wilayahs = Wilayah::when($search, function($q) use ($filterBy, $search) {
                $q->where($filterBy, 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('fwilayahid', 'desc')
            ->paginate(10)
            ->withQueryString(); 

        return view('master.wilayah.index', compact('wilayahs', 'filterBy', 'search'));
    }

    public function create()
    {
        return view('master.wilayah.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fwilayahcode' => 'required|string|unique:mswilayah,fwilayahcode',
            'fwilayahname' => 'required|string',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = "User yang membuat"; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Use the current time
        $validated['fupdatedat'] = now(); // Use the current time

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Create the new Wilayah
        Wilayah::create($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function edit($fwilayahid)
    {
        // Ambil data berdasarkan PK fwilayahid
        $wilayah = Wilayah::findOrFail($fwilayahid);

        return view('master.wilayah.edit', compact('wilayah'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fwilayahid)
    {
        // Validasi
        $validated = $request->validate([
            'fwilayahcode' => "required|string|unique:mswilayah,fwilayahcode,{$fwilayahid},fwilayahid",
            'fwilayahname' => 'required|string',
        ]);

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Cari dan update
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->update($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil di-update.');
    }

    public function destroy($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->delete();

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil dihapus.');
    }
}
