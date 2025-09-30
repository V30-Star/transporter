<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    // protected $permission = [];

    public function __construct()
    {
        $restrictedPermissions = session('user_restricted_permissions', []);

        $this->restrictedPermission = $restrictedPermissions ? explode(',', $restrictedPermissions) : [];
    }

    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all';

        // Sorting
        $allowedSorts = ['fwilayahcode', 'fwilayahname', 'fwilayahid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fwilayahid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $wilayahs = Wilayah::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'fwilayahcode') {
                    $qq->where('fwilayahcode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'fwilayahname') {
                    $qq->where('fwilayahname', 'ILIKE', "%{$search}%");
                } else {
                    $qq->where('fwilayahcode', 'ILIKE', "%{$search}%")
                        ->orWhere('fwilayahname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('fwilayahid', 'desc') // tie-breaker
            ->paginate(10)
            ->withQueryString();

        $canCreate = in_array('createWilayah', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateWilayah', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteWilayah', explode(',', session('user_restricted_permissions', '')));

        if ($request->ajax()) {
            $rows = collect($wilayahs->items())->map(function ($w) {
                return [
                    'fwilayahid'   => $w->fwilayahid,
                    'fwilayahcode' => $w->fwilayahcode,
                    'fwilayahname' => $w->fwilayahname,
                    'edit_url'     => route('wilayah.edit', $w->fwilayahid),
                    'destroy_url'  => route('wilayah.destroy', $w->fwilayahid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $wilayahs->previousPageUrl(),
                    'next'         => $wilayahs->nextPageUrl(),
                    'current_page' => $wilayahs->currentPage(),
                    'last_page'    => $wilayahs->lastPage(),
                ],
                'sort' => ['by' => $sortBy, 'dir' => $sortDir],
            ]);
        }

        return view('master.wilayah.index', compact(
            'wilayahs',
            'filterBy',
            'search',
            'canCreate',
            'canEdit',
            'canDelete',
            'sortBy',
            'sortDir'
        ));
    }

    public function create()
    {
        return view('master.wilayah.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fwilayahcode' => 'required|string|unique:mswilayah,fwilayahcode',
            'fwilayahname' => 'required|string',
        ], [
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
        ]);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Wilayah::create($validated);

        return redirect()
            ->route('wilayah.create')
            ->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function edit($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);

        return view('master.wilayah.edit', compact('wilayah'));
    }

    public function update(Request $request, $fwilayahid)
    {
        $validated = $request->validate([
            'fwilayahcode' => "required|string|unique:mswilayah,fwilayahcode,{$fwilayahid},fwilayahid",
            'fwilayahname' => 'required|string',
        ], [
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
        ]);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->update($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil di-update.');
    }

    public function destroy($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->delete();

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil dihapus.');
    }
}
