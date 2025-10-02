<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fsuppliercode', 'fsuppliername', 'fsupplierid', 'fkontakperson', 'faddress'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsupplierid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $suppliers = Supplier::orderBy($sortBy, $sortDir)->get(['fsuppliercode', 'fsuppliername', 'fsupplierid',  'fkontakperson', 'faddress']);

        $canCreate = in_array('createSupplier', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSupplier', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSupplier', explode(',', session('user_restricted_permissions', '')));

        return view('supplier.index', compact('suppliers', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
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
        // Validate the incoming data
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
        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $query = Supplier::query()
            ->select('fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftelp')
            // exclude non-active if you use 'Y' to mark inactive
            ->where(function ($w) {
                $w->whereNull('fnonactive')->orWhere('fnonactive', '!=', 'Y');
            });

        if ($q !== '') {
            // Postgres case-insensitive search
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('fsuppliercode', 'ilike', $like)
                    ->orWhere('fsuppliername', 'ilike', $like);
            });
        }

        $paginated = $query
            ->orderBy('fsuppliercode')   // adjust if you prefer name
            ->paginate($perPage);

        return response()->json($paginated);
    }
}
