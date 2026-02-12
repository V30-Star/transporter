<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use Illuminate\Http\Request;

class SalesmanController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fsalesmancode', 'fsalesmanname', 'fsalesmanid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsalesmanid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Salesman::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $salesmans = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fsalesmancode', 'fsalesmanname', 'fsalesmanid', 'fnonactive']);

        $permsStr  = (string) session('user_restricted_permissions', '');
        $permsArr  = explode(',', $permsStr);
        $canCreate = in_array('createSalesman', $permsArr, true);
        $canEdit   = in_array('updateSalesman', $permsArr, true);
        $canDelete = in_array('deleteSalesman', $permsArr, true);

        return view('salesman.index', compact('salesmans', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('salesman.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fsalesmancode' => strtoupper($request->fsalesmancode),
        ]);

        $validated = $request->validate(
            [
                'fsalesmancode' => 'required|string|unique:mssalesman,fsalesmancode',
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode Salesman wajib diisi.',
                'fsalesmanname.required' => 'Nama Salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode Salesman sudah ada.',
            ]
        );

        // Add default values for the required fields
        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Salesman
        Salesman::create($validated);

        return redirect()
            ->route('salesman.create')
            ->with('success', 'Salesman berhasil ditambahkan.');
    }

    public function edit($fsalesmanid)
    {
        // Ambil data berdasarkan PK fsalesmanid
        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.edit', [
            'salesman' => $salesman,
            'action' => 'edit'
        ]);
    }

    public function view($fsalesmanid)
    {
        // Ambil data berdasarkan PK fsalesmanid
        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.view', [
            'salesman' => $salesman
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fsalesmanid)
    {
        $request->merge([
            'fsalesmancode' => strtoupper($request->fsalesmancode),
        ]);

        $validated = $request->validate(
            [
                'fsalesmancode' => "required|string|unique:mssalesman,fsalesmancode,{$fsalesmanid},fsalesmanid",
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode Salesman wajib diisi.',
                'fsalesmanname.required' => 'Nama Salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode Salesman sudah ada.',
            ]
        );

        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        $salesman = Salesman::findOrFail($fsalesmanid);
        $salesman->update($validated);

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil di-update.');
    }

    public function delete($fsalesmanid)
    {
        $salesman = Salesman::findOrFail($fsalesmanid);
        return view('salesman.edit', [
            'salesman' => $salesman,
            'action' => 'delete'
        ]);
    }

    public function destroy($fsalesmanid)
    {
        try {
            $salesman = Salesman::findOrFail($fsalesmanid);
            $salesman->delete();

            return redirect()->route('salesman.index')->with('success', 'Data salesman ' . $salesman->fsalesmanname . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('salesman.delete', $fsalesmanid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function browse(Request $request)
    {
        // Base query
        $query = Salesman::query();

        // Total records tanpa filter
        $recordsTotal = Salesman::count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fsalesmancode', 'ilike', "%{$search}%")
                    ->orWhere('fsalesmanname', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fsalesmanname');
        $orderDir = $request->input('order_dir', 'asc');

        $allowedColumns = ['fsalesmancode', 'fsalesmanname'];
        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fsalesmanname', 'asc');
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
