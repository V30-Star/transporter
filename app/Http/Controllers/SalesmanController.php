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

    public function destroy($id)
    {
        try {
            $salesman = Salesman::findOrFail($id);
            $salesman->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data salesman berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
