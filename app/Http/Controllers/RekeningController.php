<?php

namespace App\Http\Controllers;

use App\Models\Rekening;    
use Illuminate\Http\Request;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        // Set default filter and search query
        $filterBy = in_array($request->filter_by, ['frekeningcode', 'frekeningid', 'frekeningname'])
            ? $request->filter_by
            : 'frekeningcode';

        $search = $request->search;

        // Query with search functionality
        $rekening = Rekening::when($search, function($q) use ($filterBy, $search) {
                $q->where($filterBy, 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('frekeningid', 'desc')
            ->paginate(10)
            ->withQueryString(); 

        return view('rekening.index', compact('rekening', 'filterBy', 'search'));
    }

    public function create()
    {
        return view('rekening.create');
    }

    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'frekeningcode' => 'required|string|unique:msrekening,frekeningcode',
            'frekeningname' => 'required|string',
        ]);

        // Add default values for created and updated fields
        $validated['fcreatedby'] = "User yang membuat"; // Or use the authenticated user's name
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Set current time
        $validated['fupdatedat'] = now(); // Set current time

        // Handle 'fnonactive' checkbox (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Create new Rekening record
        Rekening::create($validated);

        return redirect()
            ->route('rekening.index')
            ->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function edit($frekeningid)
    {
        // Find Rekening by primary key
        $rekening = Rekening::findOrFail($frekeningid);

        return view('rekening.edit', compact('rekening'));
    }

    public function update(Request $request, $frekeningid)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'frekeningcode' => "required|string|unique:msrekening,frekeningcode,{$frekeningid},frekeningid",
            'frekeningname' => 'required|string',
        ]);

        // Handle 'fnonactive' checkbox
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Find Rekening and update
        $rekening = Rekening::findOrFail($frekeningid);
        $rekening->update($validated);

        return redirect()
            ->route('rekening.index')
            ->with('success', 'Rekening berhasil di-update.');
    }

    public function destroy($frekeningid)
    {
        // Find Rekening and delete it
        $rekening = Rekening::findOrFail($frekeningid);
        $rekening->delete();

        return redirect()
            ->route('rekening.index')
            ->with('success', 'Rekening berhasil dihapus.');
    }
}
