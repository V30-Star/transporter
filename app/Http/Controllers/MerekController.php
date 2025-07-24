<?php

namespace App\Http\Controllers;

use App\Models\Merek;    
use Illuminate\Http\Request;

class MerekController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fmerekcode', 'fmerekid', 'fmerekname'])
            ? $request->filter_by
            : 'fmerekcode';

        $search = $request->search;

        $mereks = Merek::when($search, function($q) use ($filterBy, $search) {
                $q->where($filterBy, 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('fmerekid', 'desc')
            ->paginate(10)
            ->withQueryString(); 

        return view('merek.index', compact('mereks', 'filterBy', 'search'));
    }

    public function create()
    {
        return view('merek.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fmerekcode' => 'required|string|unique:msmerek,fmerekcode',
            'fmerekname' => 'required|string',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = "User yang membuat"; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Use the current time
        $validated['fupdatedat'] = now(); // Use the current time

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Create the new Merek
        Merek::create($validated);

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil ditambahkan.');
    }

    public function edit($fmerekid)
    {
        // Ambil data berdasarkan PK fmerekid
        $merek = Merek::findOrFail($fmerekid);

        return view('merek.edit', compact('merek'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fmerekid)
    {
        // Validasi
        $validated = $request->validate([
            'fmerekcode' => "required|string|unique:msmerek,fmerekcode,{$fmerekid},fmerekid",
            'fmerekname' => 'required|string',
        ]);

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Cari dan update
        $merek = Merek::findOrFail($fmerekid);
        $merek->update($validated);

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil di-update.');
    }

    public function destroy($fmerekid)
    {
        $merek = Merek::findOrFail($fmerekid);
        $merek->delete();

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil dihapus.');
    }
}
