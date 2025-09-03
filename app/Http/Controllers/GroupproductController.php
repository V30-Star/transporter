<?php

namespace App\Http\Controllers;

use App\Models\Groupproduct;
use Illuminate\Http\Request;

class GroupproductController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all'; // 'all' | 'fgroupcode' | 'fgroupname'

        $groupproducts = Groupproduct::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'fgroupcode') {
                    $qq->where('fgroupcode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'fgroupname') {
                    $qq->where('fgroupname', 'ILIKE', "%{$search}%");
                } else { // 'all'
                    $qq->where('fgroupcode', 'ILIKE', "%{$search}%")
                        ->orWhere('fgroupname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy('fgroupid', 'desc')
            ->paginate(10)
            ->withQueryString();

        // permissions (sesuaikan penamaan dengan app kamu)
        $canCreate = in_array('createGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupProduct', explode(',', session('user_restricted_permissions', '')));

        // Response AJAX untuk live search/pagination
        if ($request->ajax()) {
            $rows = collect($groupproducts->items())->map(function ($gp) {
                return [
                    'fgroupid'   => $gp->fgroupid,
                    'fgroupcode' => $gp->fgroupcode,
                    'fgroupname' => $gp->fgroupname,
                    'edit_url'   => route('groupproduct.edit', $gp->fgroupid),
                    'destroy_url' => route('groupproduct.destroy', $gp->fgroupid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $groupproducts->previousPageUrl(),
                    'next'         => $groupproducts->nextPageUrl(),
                    'current_page' => $groupproducts->currentPage(),
                    'last_page'    => $groupproducts->lastPage(),
                ],
            ]);
        }

        // Render awal
        return view('groupproduct.index', compact('groupproducts', 'filterBy', 'search', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('groupproduct.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fgroupcode' => 'required|string|unique:msgroupproduct,fgroupcode',
                'fgroupname' => 'required|string',
            ],
            [
                'fgroupcode.unique' => 'Kode grup produk sudah ada.',
                'fgroupcode.required' => 'Kode grup produk harus diisi.',
                'fgroupname.required' => 'Nama grup produk harus diisi.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Groupproduct
        Groupproduct::create($validated);

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Groupproduct berhasil ditambahkan.');
    }

    public function edit($fgroupid)
    {
        // Fetch the Groupproduct data by its primary key
        $groupproduct = Groupproduct::findOrFail($fgroupid);

        return view('groupproduct.edit', compact('groupproduct'));
    }

    public function update(Request $request, $fgroupid)
    {
        // Validate the incoming data
        $validated = $request->validate(
            [
                'fgroupcode' => "required|string|unique:msgroupproduct,fgroupcode,{$fgroupid},fgroupid",
                'fgroupname' => 'required|string',
            ],
            [
                'fgroupcode.unique' => 'Kode grup produk sudah ada.',
                'fgroupcode.required' => 'Kode grup produk harus diisi.',
                'fgroupname.required' => 'Nama grup produk harus diisi.',
            ]
        );

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

    public function destroy($fgroupid)
    {
        // Find and delete the Groupproduct
        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $groupproduct->delete();

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Groupproduct berhasil dihapus.');
    }
}
