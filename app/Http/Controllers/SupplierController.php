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
        ],
        [
            'fsuppliercode.unique' => 'Kode Supplier sudah ada.',
            'fsuppliercode.required' => 'Kode Supplier harus diisi.',
            'fsuppliername.required' => 'Nama Supplier harus diisi.',
            'fnpwp.required' => 'NPWP harus diisi.',
            'faddress.required' => 'Alamat harus diisi.',
            'ftelp.required' => 'Telepon harus diisi.',
            'ffax.required' => 'Fax harus diisi.',
            'fcurr.required' => 'Mata Uang harus diisi.',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = '0';

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
        ],
        [
            'fsuppliercode.unique' => 'Kode Supplier sudah ada.',
            'fsuppliercode.required' => 'Kode Supplier harus diisi.',
            'fsuppliername.required' => 'Nama Supplier harus diisi.',
            'fnpwp.required' => 'NPWP harus diisi.',
            'faddress.required' => 'Alamat harus diisi.',
            'ftelp.required' => 'Telepon harus diisi.',
            'ffax.required' => 'Fax harus diisi.',
            'fcurr.required' => 'Mata Uang harus diisi.',
        ]);

        $validated['fnonactive'] = '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

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
