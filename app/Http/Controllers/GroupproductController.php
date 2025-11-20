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

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Groupproduct
        Groupproduct::create($validated);

        return redirect()
            ->route('groupproduct.create')
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
                'fgroupcode' => "required|string|unique:ms_groupprd,fgroupcode,{$fgroupid},fgroupid",
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

    public function browse(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $query = Groupproduct::query()
            ->select('fgroupid', 'fgroupcode', 'fgroupname', 'fnonactive');

        // Jika ingin exclude non-active (sesuaikan definisi non aktif Anda)
        $query->where(function ($w) {
            $w->whereNull('fnonactive')
                ->orWhere('fnonactive', '!=', '1')
                ->orWhere('fnonactive', '!=', 'Y');
        });

        if ($q !== '') {
            // Jika Postgres: pakai ILIKE, jika MySQL: LIKE (case-insensitive tergantung collation)
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                // ganti 'ilike' jika pakai PostgreSQL
                $w->where('fgroupcode', 'like', $like)
                    ->orWhere('fgroupname', 'like', $like);
            });
        }

        $paginated = $query->orderBy('fgroupcode')->paginate($perPage);

        return response()->json($paginated);
    }
}
