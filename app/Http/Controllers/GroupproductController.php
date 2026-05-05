<?php

namespace App\Http\Controllers;

use App\Models\Groupproduct;
use Illuminate\Http\Request;

class GroupproductController extends Controller
{
    public function index(Request $request)
    {
        $groupproducts = Groupproduct::orderBy('fgroupcode', 'asc')
            ->get(['fgroupcode', 'fgroupname', 'fgroupid', 'fnonactive']);

        $canCreate = in_array('createGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupProduct', explode(',', session('user_restricted_permissions', '')));

        return view('groupproduct.index', compact('groupproducts', 'canCreate', 'canEdit', 'canDelete'));
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

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->input('fnonactive', 0) == 1 ? '1' : '0';

        $group = Groupproduct::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'id' => $group->fgroupid,
                'name' => $group->fgroupname,
                'code' => $group->fgroupcode,
            ]);
        }

        return redirect()
            ->route('groupproduct.create')
            ->with('success', 'Groupproduct berhasil ditambahkan.');
    }

    public function edit($fgroupid)
    {
        $groupproduct = Groupproduct::findOrFail($fgroupid);

        return view('groupproduct.edit', [
            'groupproduct' => $groupproduct,
            'action' => 'edit',
        ]);
    }

    public function update(Request $request, $fgroupid)
    {
        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);
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
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $groupproduct->update($validated);

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Groupproduct berhasil di-update.');
    }

    public function delete($fgroupid)
    {
        $groupproduct = Groupproduct::findOrFail($fgroupid);

        return view('groupproduct.delete', [
            'groupproduct' => $groupproduct,
        ]);
    }

    public function destroy($fgroupid)
    {
        try {
            $groupproduct = Groupproduct::findOrFail($fgroupid);

            if (\Illuminate\Support\Facades\DB::table('msprd')->where('fgroupcode', $groupproduct->fgroupcode)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group Product sudah digunakan dalam data Product.',
                ], 422);
            }

            $groupproduct->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data groupproduct '.$groupproduct->fgroupname.' berhasil dihapus.',
                'redirect' => route('groupproduct.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        $query = Groupproduct::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fgroupcode', 'ilike', "%{$search}%")
                    ->orWhere('fgroupname', 'ilike', "%{$search}%");
            });
        }

        $recordsTotal = Groupproduct::count();
        $recordsFiltered = $query->count();

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
            'data' => $data,
        ]);
    }
}
