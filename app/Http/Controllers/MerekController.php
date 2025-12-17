<?php

namespace App\Http\Controllers;

use App\Models\Merek;
use Illuminate\Http\Request;

class MerekController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fmerekcode', 'fmerekname', 'fmerekid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fmerekid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Merek::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $mereks = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fmerekid', 'fmerekcode', 'fmerekname', 'fnonactive']);

        $permsStr  = (string) session('user_restricted_permissions', '');
        $permsArr  = explode(',', $permsStr);
        $canCreate = in_array('createMerek', $permsArr, true);
        $canEdit   = in_array('updateMerek', $permsArr, true);
        $canDelete = in_array('deleteMerek', $permsArr, true);

        return view('merek.index', compact('mereks', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('merek.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fmerekcode' => strtoupper($request->fmerekcode),
        ]);

        $validated = $request->validate(
            [
                'fmerekcode' => 'required|string|unique:msmerek,fmerekcode',
                'fmerekname' => 'required|string',
            ],
            [
                'fmerekcode.required' => 'Kode Merek harus diisi.',
                'fmerekname.required' => 'Nama Merek harus diisi.',
                'fmerekcode.unique' => 'Kode Merek sudah ada',
            ]
        );

        $validated['fmerekcode'] = strtoupper($validated['fmerekcode']);
        $validated['fmerekname'] = strtoupper($validated['fmerekname']);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Merek
        Merek::create($validated);

        return redirect()
            ->route('merek.create')
            ->with('success', 'Merek berhasil ditambahkan.');
    }

    public function edit($fmerekid)
    {
        // Ambil data berdasarkan PK fmerekid
        $merek = Merek::findOrFail($fmerekid);

        return view('merek.edit', [
            'merek' => $merek,
            'action' => 'edit'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fmerekid)
    {
        $request->merge([
            'fmerekcode' => strtoupper($request->fmerekcode),
        ]);

        $validated = $request->validate(
            [
                'fmerekcode' => "required|string|unique:msmerek,fmerekcode,{$fmerekid},fmerekid",
                'fmerekname' => 'required|string',
            ],
            [
                'fmerekcode.required' => 'Kode Merek harus diisi.',
                'fmerekname.required' => 'Nama Merek harus diisi.',
                'fmerekcode.unique' => 'Kode Merek sudah ada',
            ]
        );

        $validated['fmerekcode'] = strtoupper($validated['fmerekcode']);
        $validated['fmerekname'] = strtoupper($validated['fmerekname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        $merek = Merek::findOrFail($fmerekid);
        $merek->update($validated);

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil di-update.');
    }

    public function delete($fmerekid)
    {
        $merek = Merek::findOrFail($fmerekid);
        return view('merek.edit', [
            'merek' => $merek,
            'action' => 'delete'
        ]);
    }

    public function destroy($fmerekid)
    {
        try {
            $merek = Merek::findOrFail($fmerekid);
            $merek->delete();

            return redirect()->route('merek.index')->with('success', 'Data merek ' . $merek->fmerekname . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('merek.delete', $fmerekid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function browse(Request $request)
    {
        // Base query
        $query = Merek::query();

        // Total records tanpa filter
        $recordsTotal = Merek::count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            // Parameter search dari DataTables dikirim di $request->search
            $query->where(function ($q) use ($search) {
                $q->where('fmerekcode', 'ilike', "%{$search}%")
                    ->orWhere('fmerekname', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fmerekname');
        $orderDir = $request->input('order_dir', 'asc');

        // Kolom yang diizinkan untuk di-sorting
        $allowedColumns = ['fmerekcode', 'fmerekname'];

        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            // Default order
            $query->orderBy('fmerekname', 'asc');
        }

        // Pagination (Menggunakan start dan length dari DataTables)
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
