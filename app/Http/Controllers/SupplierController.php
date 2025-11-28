<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fsuppliercode', 'fsuppliername', 'fsupplierid', 'fkontakperson', 'faddress', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsupplierid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Supplier::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $suppliers = Supplier::orderBy($sortBy, $sortDir)->get(['fsuppliercode', 'fsuppliername', 'fsupplierid',  'fkontakperson', 'faddress', 'fnonactive']);

        $canCreate = in_array('createSupplier', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSupplier', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSupplier', explode(',', session('user_restricted_permissions', '')));

        return view('supplier.index', compact('suppliers', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fsuppliercode' => strtoupper($request->fsuppliercode),
            'fsuppliername' => strtoupper($request->fsuppliername),
        ]);

        $validated = $request->validate(
            [
                'fsuppliercode' => 'required|string|unique:mssupplier,fsuppliercode',
                'fsuppliername' => 'required|string',
                'fnpwp' => 'required|string',
                'faddress' => 'required|string',
                'fkontakperson' => '',
                'ftelp' => 'required|string',
                'ffax' => 'required|string',
                'fcurr' => 'required|string',
                'fjabatan' => '',
                'ftempo' => '',
                'fcity' => '',
                'fmemo' => '',
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
            ]
        );

        $validated['fsuppliercode'] = strtoupper($validated['fsuppliercode']);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Supplier
        Supplier::create($validated);

        return redirect()
            ->route('supplier.create')
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
        $request->merge([
            'fsuppliercode' => strtoupper($request->fsuppliercode),
            'fsuppliername' => strtoupper($request->fsuppliername),
        ]);

        $validated = $request->validate(
            [
                'fsuppliercode' => "required|string|unique:mssupplier,fsuppliercode,{$fsupplierid},fsupplierid",
                'fsuppliername' => 'required|string',
                'fnpwp' => 'required|string',
                'fkontakperson' => '',
                'fjabatan' => '',
                'ftempo' => '',
                'fmemo' => '',
                'faddress' => 'required|string',
                'ftelp' => 'required|string',
                'ffax' => 'required|string',
                'fcurr' => 'required|string', // Validate the currency field (fcurr)
                'fcity' => '', // Validate the currency field (fcurr)
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
            ]
        );


        $validated['fsuppliercode'] = strtoupper($validated['fsuppliercode']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
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
    public function browse(Request $request)
    {
        $query = Supplier::query();

        // Search
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('fsuppliercode', 'ilike', "%{$search}%")
                    ->orWhere('fsuppliername', 'ilike', "%{$search}%")
                    ->orWhere('faddress', 'ilike', "%{$search}%")
                    ->orWhere('ftelp', 'ilike', "%{$search}%");
            });
        }

        // Get total before pagination
        $recordsTotal = Supplier::count();
        $recordsFiltered = $query->count();

        // Pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $data = $query->orderBy('fsuppliername', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format response untuk DataTables
        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }
}
