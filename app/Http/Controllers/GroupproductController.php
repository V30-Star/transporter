<?php

namespace App\Http\Controllers;

use App\Models\Groupproduct;
use Illuminate\Http\Request;

class GroupproductController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fgroupcode', 'fgroupname', 'fgroupid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fgroupid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Groupproduct::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $groupproducts = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fgroupcode', 'fgroupname', 'fgroupid', 'fnonactive']);

        $canCreate = in_array('createGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupProduct', explode(',', session('user_restricted_permissions', '')));

        return view('groupproduct.index', compact('groupproducts', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('groupproduct.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);

        $validated = $request->validate(
            [
                'fgroupcode' => 'required|string|unique:ms_groupprd,fgroupcode',
                'fgroupname' => 'required|string',
            ],
            [
                'fgroupcode.unique' => 'Kode grup produk sudah ada.',
                'fgroupcode.required' => 'Kode grup produk harus diisi.',
                'fgroupname.required' => 'Nama grup produk harus diisi.',
            ]
        );

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->input('fnonactive', 0) == 1 ? '1' : '0';

        // Create the new Groupproduct
        $group = Groupproduct::create($validated);
        
        if ($request->ajax()) {
            return response()->json([
                'id'   => $group->fgroupid,   // Pastikan ini nama Primary Key di tabel Anda
                'name' => $group->fgroupname,
                'code' => $group->fgroupcode
            ]);
        }

        return redirect()
            ->route('groupproduct.create')
            ->with('success', 'Groupproduct berhasil ditambahkan.');
    }

    public function edit($fgroupid)
    {
        // Fetch the Groupproduct data by its primary key
        $groupproduct = Groupproduct::findOrFail($fgroupid);

        return view('groupproduct.edit', [
            'groupproduct' => $groupproduct,
            'action' => 'delete'
        ]);
    }

    public function update(Request $request, $fgroupid)
    {
        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);
        // Validate the incoming data
        $validated = $request->validate(
            [
                'fgroupcode' => "required|string|unique:ms_groupprd,fgroupcode,{$fgroupid},fgroupid",
                'fgroupname' => 'required|string',
            ],
            [
                'fgroupcode.unique' => 'Kode grup produk sudah ada.',
                'fgroupcode.required' => 'Kode grup produk harus diisi.',
                'fgroupname.required' => 'Nama grup produk harus diisi.',
            ]
        );

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Find and update the Groupproduct
        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $groupproduct->update($validated);

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Groupproduct berhasil di-update.');
    }

    public function delete($fgroupid)
    {
        $groupproduct = Groupproduct::findOrFail($fgroupid);
        return view('groupproduct.edit', [
            'groupproduct' => $groupproduct,
            'action' => 'delete'
        ]);
    }

    public function destroy($fgroupid)
    {
        try {
            $groupproduct = Groupproduct::findOrFail($fgroupid);
            $groupproduct->delete();

            return redirect()->route('groupproduct.index')->with('success', 'Data groupproduct ' . $groupproduct->fgroupname . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('groupproduct.delete', $fgroupid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function browse(Request $request)
    {
        $query = Groupproduct::query(); // Atau ProductGroup::query() sesuai model Anda

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fgroupcode', 'ilike', "%{$search}%")
                    ->orWhere('fgroupname', 'ilike', "%{$search}%");
            });
        }

        // Get totals
        $recordsTotal = Groupproduct::count();
        $recordsFiltered = $query->count();

        // Pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $data = $query->orderBy('fgroupcode', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }
}
