<?php

namespace App\Http\Controllers;

use App\Models\Merek;
use Illuminate\Http\Request;

class MerekController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all'; // 'all' | 'fmerekcode' | 'fmerekid' | 'fmerekname'

        $mereks = Merek::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'fmerekcode') {
                    $qq->where('fmerekcode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'fmerekid') {
                    // jika numeric dan mau cari mengandung, cast ke text
                    $qq->whereRaw('CAST(fmerekid AS TEXT) ILIKE ?', ["%{$search}%"]);
                } elseif ($filterBy === 'fmerekname') {
                    $qq->where('fmerekname', 'ILIKE', "%{$search}%");
                } else { // 'all'
                    $qq->where('fmerekcode', 'ILIKE', "%{$search}%")
                        ->orWhereRaw('CAST(fmerekid AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhere('fmerekname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy('fmerekid', 'desc')
            ->paginate(10)
            ->withQueryString();

        // permissions (samakan penamaannya dengan app kamu)
        $canCreate = in_array('createMerek', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateMerek', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteMerek', explode(',', session('user_restricted_permissions', '')));

        // Respon AJAX untuk live search/pagination
        if ($request->ajax()) {
            $rows = collect($mereks->items())->map(function ($m) {
                return [
                    'fmerekid'   => $m->fmerekid,
                    'fmerekcode' => $m->fmerekcode,
                    'fmerekname' => $m->fmerekname,
                    'edit_url'   => route('merek.edit', $m->fmerekid),
                    'destroy_url' => route('merek.destroy', $m->fmerekid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $mereks->previousPageUrl(),
                    'next'         => $mereks->nextPageUrl(),
                    'current_page' => $mereks->currentPage(),
                    'last_page'    => $mereks->lastPage(),
                ],
            ]);
        }

        // Render awal (Blade)
        return view('merek.index', compact('mereks', 'filterBy', 'search', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function ajaxStore(Request $request)
    {
        $data = $request->validate([
            // pastikan nama tabel di rule unique sama dengan modelmu
            'fmerekcode' => ['required', 'string', 'max:50', 'unique:msmerek,fmerekcode'],
            'fmerekname' => ['required', 'string', 'max:100'],
            'fnonactive' => ['nullable', 'in:0,1'],
        ]);

        $userName = optional(auth('sysuser')->user())->fname ?? 'system';
        $now = now();

        $merek = Merek::create([
            'fmerekcode'  => $data['fmerekcode'],
            'fmerekname'  => $data['fmerekname'],
            'fnonactive'  => $request->boolean('fnonactive') ? 1 : 0,
            'fcreatedby'  => $userName,
            'fupdatedby'  => $userName,
            'fcreatedat'  => $now,
            'fupdatedat'  => $now,
        ]);

        return response()->json([
            'id'   => $merek->fmerekid,
            'name' => $merek->fmerekname,
        ], 201);
    }

    public function create()
    {
        return view('merek.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fmerekcode' => 'required|string|unique:msmerek,fmerekcode',
                'fmerekname' => 'required|string',
            ],
            [
                'fmerekcode.required' => 'Kode Merek harus diisi.',
                'fmerekname.required' => 'Nama Merek harus diisi.',
                'fmerekcode.unique' => 'Kode Merek sudah ada, silakan gunakan kode lain.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Merek
        Merek::create($validated);

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil ditambahkan.');
    }

    public function edit($fmerekid)
    {
        // Ambil data berdasarkan PK fmerekid
        $merek = Merek::findOrFail($fmerekid);

        return view('merek.edit', compact('merek'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fmerekid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fmerekcode' => "required|string|unique:msmerek,fmerekcode,{$fmerekid},fmerekid",
                'fmerekname' => 'required|string',
            ],
            [
                'fmerekcode.required' => 'Kode Merek harus diisi.',
                'fmerekname.required' => 'Nama Merek harus diisi.',
                'fmerekcode.unique' => 'Kode Merek sudah ada, silakan gunakan kode lain.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        $merek = Merek::findOrFail($fmerekid);
        $merek->update($validated);

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil di-update.');
    }

    public function destroy($fmerekid)
    {
        $merek = Merek::findOrFail($fmerekid);
        $merek->delete();

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil dihapus.');
    }

    public function browse(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $query = Merek::query()
            ->select('fmerekid', 'fmerekcode', 'fmerekname', 'fnonactive')
            // hanya aktif (sesuaikan logika aktif/nonaktif Anda)
            ->where(function ($w) {
                $w->whereNull('fnonactive')->orWhere('fnonactive', '!=', '1')->orWhere('fnonactive', '!=', 'Y');
            });

        if ($q !== '') {
            // jika pakai Postgres: 'ilike', jika MySQL pakai 'like'
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('fmerekcode', 'like', $like)
                    ->orWhere('fmerekname', 'like', $like);
            });
        }

        $paginated = $query->orderBy('fmerekcode')->paginate($perPage);

        return response()->json($paginated);
    }
}
