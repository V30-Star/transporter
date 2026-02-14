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
        $validated['fsuppliername'] = strtoupper($validated['fsuppliername']);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
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
        return view('supplier.edit', [
            'supplier' => $supplier,
            'action' => 'edit'
        ]);
    }
    
    public function view($fsupplierid)
    {
        // Fetch the Supplier data by its primary key
        $supplier = Supplier::findOrFail($fsupplierid);

        // Pass the supplier data to the view view
        return view('supplier.view', [
            'supplier' => $supplier
        ]);
    }

    public function update(Request $request, $fsupplierid)
    {
        $request->merge([
            'fsuppliercode' => strtoupper($request->fsuppliercode),
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
        $validated['fsuppliername'] = strtoupper($validated['fsuppliername']);

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

    public function delete($fsupplierid)
    {
        $supplier = Supplier::findOrFail($fsupplierid);
        return view('supplier.edit', [
            'supplier' => $supplier,
            'action' => 'delete'
        ]);
    }

    public function destroy($fsupplierid)
    {
        try {
            $supplier = Supplier::findOrFail($fsupplierid);
            $supplier->delete();

            return redirect()->route('supplier.index')->with('success', 'Data supplier ' . $supplier->fsuppliername . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('supplier.delete', $fsupplierid)->with('error', 'Gakey: gal menghapus data: ' . $e->getMessage());
        }
    }

    public function browse(Request $request)
    {
        // Base query
        $query = Supplier::query();

        // Total records tanpa filter
        $recordsTotal = Supplier::count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fsuppliercode', 'ilike', "%{$search}%")
                    ->orWhere('fsuppliername', 'ilike', "%{$search}%")
                    ->orWhere('faddress', 'ilike', "%{$search}%")
                    ->orWhere('ftelp', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fsuppliername');
        $orderDir = $request->input('order_dir', 'asc');

        $allowedColumns = ['fsuppliercode', 'fsuppliername', 'faddress', 'ftelp'];
        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fsuppliername', 'asc');
        }

        // Pagination
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)
            ->take($length)
            ->get();

        // Response format untuk DataTables
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data
        ]);
    }
}
