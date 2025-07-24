<?php

namespace App\Http\Controllers;

use App\Models\Salesman;    
use Illuminate\Http\Request;

class SalesmanController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fsalesmancode', 'fsalesmanname'])
            ? $request->filter_by
            : 'fsalesmancode';

        $search = $request->search;

        $salesmen = Salesman::when($search, function($q) use ($filterBy, $search) {
                $q->where($filterBy, 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('fsalesmanid', 'desc')
            ->paginate(10)
            ->withQueryString(); 

        return view('salesman.index', compact('salesmen', 'filterBy', 'search'));
    }

    public function create()
    {
        return view('salesman.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fsalesmancode' => 'required|string|unique:mssalesman,fsalesmancode',
            'fsalesmanname' => 'required|string',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = "User yang membuat"; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Use the current time
        $validated['fupdatedat'] = now(); // Use the current time

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Create the new Salesman
        Salesman::create($validated);

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil ditambahkan.');
    }

    public function edit($fsalesmanid)
    {
        // Ambil data berdasarkan PK fsalesmanid
        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.edit', compact('salesman'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fsalesmanid)
    {
        // Validasi
        $validated = $request->validate([
            'fsalesmancode' => "required|string|unique:mssalesman,fsalesmancode,{$fsalesmanid},fsalesmanid",
            'fsalesmanname' => 'required|string',
        ]);

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Cari dan update
        $salesman = Salesman::findOrFail($fsalesmanid);
        $salesman->update($validated);

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil di-update.');
    }

    public function destroy($fsalesmanid)
    {
        $salesman = Salesman::findOrFail($fsalesmanid);
        $salesman->delete();

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil dihapus.');
    }
}
