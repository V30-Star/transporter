<?php

namespace App\Http\Controllers;

use App\Models\Wh;
use App\Models\Cabang;
use Illuminate\Http\Request;

class WhController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all';

        // Sorting (klik header)
        $allowedSorts = ['fwhcode', 'fwhname', 'fwhid']; // tambahkan kolom lain jika perlu
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fwhid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $gudangs = Wh::with('cabang')
            ->when($search !== '', function ($q) use ($search, $filterBy) {
                $q->where(function ($qq) use ($search, $filterBy) {
                    if ($filterBy === 'fwhcode') {
                        $qq->where('fwhcode', 'ILIKE', "%{$search}%");
                    } elseif ($filterBy === 'fwhid') {
                        $qq->whereRaw('CAST(fwhid AS TEXT) ILIKE ?', ["%{$search}%"]);
                    } elseif ($filterBy === 'fwhname') {
                        $qq->where('fwhname', 'ILIKE', "%{$search}%");
                    } else { // 'all'
                        $qq->where('fwhcode', 'ILIKE', "%{$search}%")
                            ->orWhereRaw('CAST(fwhid AS TEXT) ILIKE ?', ["%{$search}%"])
                            ->orWhere('fwhname', 'ILIKE', "%{$search}%");
                    }
                });
            })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('fwhid', 'desc') // tie-breaker
            ->paginate(10)
            ->withQueryString();

        // permissions
        $canCreate = in_array('createGudang', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateGudang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGudang', explode(',', session('user_restricted_permissions', '')));

        // Respon AJAX
        if ($request->ajax()) {
            $rows = collect($gudangs->items())->map(function ($g) {
                return [
                    'fwhid'       => $g->fwhid,
                    'fwhcode'     => $g->fwhcode,
                    'fwhname'     => $g->fwhname,
                    'cabang_code' => optional($g->cabang)->fcabangkode ?? null,
                    'cabang_name' => optional($g->cabang)->fcabangname ?? null,
                    'faddress'    => $g->faddress ?? null, // kalau ada kolom alamat di tabel wh
                    'edit_url'    => route('gudang.edit', $g->fwhid),
                    'destroy_url' => route('gudang.destroy', $g->fwhid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $gudangs->previousPageUrl(),
                    'next'         => $gudangs->nextPageUrl(),
                    'current_page' => $gudangs->currentPage(),
                    'last_page'    => $gudangs->lastPage(),
                ],
                'sort' => ['by' => $sortBy, 'dir' => $sortDir], // penting untuk ikon di front-end
            ]);
        }

        // Render awal (Blade)
        return view('gudang.index', compact(
            'gudangs',
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
        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.create', compact('cabangOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fwhcode' => 'required|string|unique:mswh,fwhcode',
                'fwhname' => 'required|string',
                'faddress' => 'required|string',
                'fbranchcode' => 'required|string',
            ],
            [
                'fwhcode.unique' => 'Kode Gudang sudah ada.',
                'fwhcode.required' => 'Kode Gudang harus diisi.',
                'fwhname.required' => 'Nama Gudang harus diisi.',
                'faddress.required' => 'Alamat Gudang harus diisi.',
                'fbranchcode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Wh::create($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Wh berhasil ditambahkan.');
    }

    public function edit($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);

        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.edit', compact('gudang', 'cabangOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fwhid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fwhcode' => "required|string|unique:mswh,fwhcode,{$fwhid},fwhid",
                'fwhname' => 'required|string',
                'faddress' => 'required|string',
                'fbranchcode' => 'required|string', // Ensure the cabang code is validated and passed
            ],
            [
                'fwhcode.unique' => 'Kode Wh sudah ada.',
                'fwhcode.required' => 'Kode Wh harus diisi.',
                'fwhname.required' => 'Nama Wh harus diisi.',
                'faddress.required' => 'Alamat Wh harus diisi.',
                'fbranchcode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        $gudang = Wh::findOrFail($fwhid);
        $gudang->update($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Wh berhasil di-update.');
    }

    public function destroy($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);
        $gudang->delete();

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Wh berhasil dihapus.');
    }
}
