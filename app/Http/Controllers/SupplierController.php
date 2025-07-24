<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fsuppliercode', 'fsuppliername'])
            ? $request->filter_by
            : 'fsuppliercode';

        $search = $request->search;

        $suppliers = Supplier::when($search, function($q) use ($filterBy, $search) {
                $q->where($filterBy, 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('fsupplierid', 'desc')
            ->paginate(10)
            ->withQueryString(); 

        return view('supplier.index', compact('suppliers', 'filterBy', 'search'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fsuppliercode' => 'required|string|unique:mssupplier,fsuppliercode',
            'fsuppliername' => 'required|string',
            'fnpwp' => 'required|string',
            'faddress' => 'required|string',
            'ftelp' => 'required|string',
            'ffax' => 'required|string',
            'fcurr' => 'required|string',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = "User yang membuat"; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Use the current time
        $validated['fupdatedat'] = now(); // Use the current time

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Create the new Supplier
        Supplier::create($validated);

        return redirect()
            ->route('supplier.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit($fsupplierid)
    {
        // Fetch the Supplier data by its primary key
        $supplier = Supplier::findOrFail($fsupplierid);

        // Pass the supplier data to the edit view
        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, $fsupplierid)
    {
        // Validate the incoming data
        $validated = $request->validate([
            'fsuppliercode' => "required|string|unique:mssupplier,fsuppliercode,{$fsupplierid},fsupplierid",
            'fsuppliername' => 'required|string',
            'fnpwp' => 'required|string',
            'faddress' => 'required|string',
            'ftelp' => 'required|string',
            'ffax' => 'required|string',
            'fcurr' => 'required|string', // Validate the currency field (fcurr)
        ]);

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Find and update the Supplier
        $supplier = Supplier::findOrFail($fsupplierid);
        $supplier->update($validated);

        // Redirect to the supplier index page with a success message
        return redirect()
            ->route('supplier.index')
            ->with('success', 'Supplier berhasil di-update.');
    }

    public function destroy($fsupplierid)
    {
        // Find and delete the Supplier
        $supplier = Supplier::findOrFail($fsupplierid);
        $supplier->delete();

        return redirect()
            ->route('supplier.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }
}
