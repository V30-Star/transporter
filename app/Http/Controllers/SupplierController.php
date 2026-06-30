<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    private function ensureSupplierPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu supplier.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureSupplierPermission('viewSupplier')) {
            return $guard;
        }

        $allowedSorts = ['fsuppliercode', 'fsuppliername', 'fsupplierid', 'fkontakperson', 'faddress', 'fnonactive'];
        $sortBy = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsupplierid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $suppliers = Supplier::orderBy($sortBy, $sortDir)->get(['fsuppliercode', 'fsuppliername', 'fsupplierid',  'fkontakperson', 'faddress', 'fnonactive']);

        $canCreate = in_array('createSupplier', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateSupplier', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSupplier', explode(',', session('user_restricted_permissions', '')));

        return view('supplier.index', compact('suppliers', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        if ($guard = $this->ensureSupplierPermission('createSupplier')) {
            return $guard;
        }

        return view('supplier.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureSupplierPermission('createSupplier')) {
            return $guard;
        }

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
                'ftelp' => 'nullable|string',
                'ffax' => 'nullable|string',
                'fcurr' => 'required|string',
                'fjabatan' => '',
                'ftempo' => '',
                'faddress' => '',
                'fmemo' => '',
            ],
            [
                'fsuppliercode.unique' => 'Kode supplier sudah ada.',
                'fsuppliercode.required' => 'Kode supplier wajib diisi.',
                'fsuppliername.required' => 'Nama supplier wajib diisi.',
                'fnpwp.required' => 'Npwp wajib diisi.',
                'faddress.required' => 'Alamat wajib diisi.',
                'fcurr.required' => 'Mata uang wajib diisi.',
            ]
        );

        $validated['fsuppliercode'] = strtoupper($validated['fsuppliercode']);
        $validated['fsuppliername'] = strtoupper($validated['fsuppliername']);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';

        // Create the new Supplier
        Supplier::create($validated);

        return redirect()
            ->route('supplier.create')
            ->with('success', 'Supplier berhasil disimpan.');
    }

    public function edit($fsupplierid)
    {
        if ($guard = $this->ensureSupplierPermission('updateSupplier')) {
            return $guard;
        }

        $supplier = Supplier::findOrFail($fsupplierid);
        $isTransactionLocked = $this->hasTransactionUsage($supplier);

        return view('supplier.edit', [
            'supplier' => $supplier,
            'isTransactionLocked' => $isTransactionLocked,
            'action' => 'edit',
        ]);
    }

    public function view($fsupplierid)
    {
        if ($guard = $this->ensureSupplierPermission('viewSupplier')) {
            return $guard;
        }

        $supplier = Supplier::findOrFail($fsupplierid);

        return view('supplier.view', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, $fsupplierid)
    {
        if ($guard = $this->ensureSupplierPermission('updateSupplier')) {
            return $guard;
        }

        $supplier = Supplier::findOrFail($fsupplierid);
        $isTransactionLocked = $this->hasTransactionUsage($supplier);

        $request->merge([
            'fsuppliercode' => strtoupper($isTransactionLocked ? $supplier->fsuppliercode : $request->fsuppliercode),
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
                'ftelp' => 'nullable|string',
                'ffax' => 'nullable|string',
                'fcurr' => 'required|string',
                'faddress' => '',
            ],
            [
                'fsuppliercode.unique' => 'Kode supplier sudah ada.',
                'fsuppliercode.required' => 'Kode supplier wajib diisi.',
                'fsuppliername.required' => 'Nama supplier wajib diisi.',
                'fnpwp.required' => 'Npwp wajib diisi.',
                'faddress.required' => 'Alamat wajib diisi.',
                'fcurr.required' => 'Mata uang wajib diisi.',
            ]
        );

        $validated['fsuppliercode'] = strtoupper($validated['fsuppliercode']);
        $validated['fsuppliername'] = strtoupper($validated['fsuppliername']);

        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        if ($isTransactionLocked) {
            $validated['fsuppliercode'] = $supplier->fsuppliercode;
        }

        try {
            $supplier->update($validated);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Supplier belum bisa diupdate. Cek data supplier.']);
        }

        return redirect()
            ->route('supplier.index')
            ->with('success', 'Supplier berhasil diupdate.');
    }

    public function delete($fsupplierid)
    {
        if ($guard = $this->ensureSupplierPermission('deleteSupplier')) {
            return $guard;
        }

        $supplier = Supplier::findOrFail($fsupplierid);

        if ($message = $this->getUsageLockMessage($supplier)) {
            return redirect()->route('supplier.view', $supplier->fsupplierid)->with('error', $message);
        }

        return view('supplier.delete', [
            'supplier' => $supplier,
        ]);
    }

    public function destroy($fsupplierid)
    {
        if (! $this->hasRestrictedPermission('deleteSupplier')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu supplier.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu supplier.');
        }

        try {
            $supplier = Supplier::findOrFail($fsupplierid);

            if ($message = $this->getUsageLockMessage($supplier)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('supplier.view', $supplier->fsupplierid),
                    ], 422);
                }

                return redirect()->route('supplier.view', $supplier->fsupplierid)->with('error', $message);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier ' . $supplier->fsuppliername . ' berhasil dihapus.',
                'redirect' => route('supplier.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        if ($guard = $this->ensureSupplierPermission('viewSupplier')) {
            return $guard;
        }

        $query = Supplier::query();

        $recordsTotal = Supplier::count();

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fsuppliercode', 'ilike', "%{$search}%")
                    ->orWhere('fsuppliername', 'ilike', "%{$search}%")
                    ->orWhere('faddress', 'ilike', "%{$search}%")
                    ->orWhere('ftelp', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fsuppliername');
        $orderDir = $request->input('order_dir', 'asc');

        $allowedColumns = ['fsuppliercode', 'fsuppliername', 'faddress', 'ftelp'];
        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fsuppliername', 'asc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function hasTransactionUsage(Supplier $supplier): bool
    {
        $supplierCode = trim((string) $supplier->fsuppliercode);

        if ($supplierCode === '') {
            return false;
        }

        return DB::table('tr_prh')->where('fsupplier', $supplierCode)->exists()
            || DB::table('tr_poh')->where('fsupplier', $supplierCode)->exists()
            || DB::table('trstockmt')->where('fsupplier', $supplierCode)->exists();
    }

    private function getUsageLockMessage(Supplier $supplier): ?string
    {
        if (! $this->hasTransactionUsage($supplier)) {
            return null;
        }

        return 'Supplier ' . strtoupper((string) $supplier->fsuppliercode) . ' tidak bisa dihapus. Sudah direferensi di transaksi.';
    }
}
