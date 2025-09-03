<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Cabang;
use Illuminate\Http\Request;

class GudangController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all'; // 'all' | 'fgudangcode' | 'fgudangid' | 'fgudangname'

        $gudangs = Gudang::with('cabang') // eager load relasi cabang
            ->when($search !== '', function ($q) use ($search, $filterBy) {
                $q->where(function ($qq) use ($search, $filterBy) {
                    if ($filterBy === 'fgudangcode') {
                        $qq->where('fgudangcode', 'ILIKE', "%{$search}%");
                    } elseif ($filterBy === 'fgudangid') {
                        $qq->whereRaw('CAST(fgudangid AS TEXT) ILIKE ?', ["%{$search}%"]);
                    } elseif ($filterBy === 'fgudangname') {
                        $qq->where('fgudangname', 'ILIKE', "%{$search}%");
                    } else { // 'all'
                        $qq->where('fgudangcode', 'ILIKE', "%{$search}%")
                            ->orWhereRaw('CAST(fgudangid AS TEXT) ILIKE ?', ["%{$search}%"])
                            ->orWhere('fgudangname', 'ILIKE', "%{$search}%");
                    }
                });
            })
            ->orderBy('fgudangid', 'desc')
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
                    'fgudangid'   => $g->fgudangid,
                    'fgudangcode' => $g->fgudangcode,
                    'fgudangname' => $g->fgudangname,
                    // contoh data relasi cabang (sesuaikan field yang kamu butuh)
                    'cabang_code' => optional($g->cabang)->fcabangcode ?? null,
                    'cabang_name' => optional($g->cabang)->fcabangname ?? null,

                    'edit_url'    => route('gudang.edit', $g->fgudangid),
                    'destroy_url' => route('gudang.destroy', $g->fgudangid),
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
            ]);
        }

        // Render awal (Blade)
        return view('gudang.index', compact('gudangs', 'filterBy', 'search', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        // Fetch all cabang records for the dropdown
        $cabangOptions = Cabang::where('fnonactive', 0)->get();

        // Return the create view with the cabangOptions data
        return view('gudang.create', compact('cabangOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fgudangcode' => 'required|string|unique:msgudang,fgudangcode',
                'fgudangname' => 'required|string',
                'faddress' => 'required|string',
                'fcabangkode' => 'required|string', // Ensure the cabang code is validated and passed
            ],
            [
                'fgudangcode.unique' => 'Kode Gudang sudah ada.',
                'fgudangcode.required' => 'Kode Gudang harus diisi.',
                'fgudangname.required' => 'Nama Gudang harus diisi.',
                'faddress.required' => 'Alamat Gudang harus diisi.',
                'fcabangkode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Gudang, including the `fcabangkode` field
        Gudang::create($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function edit($fgudangid)
    {
        // Fetch the Gudang record by ID
        $gudang = Gudang::findOrFail($fgudangid);

        // Fetch all cabang records for the dropdown
        $cabangOptions = Cabang::where('fnonactive', 0)->get();

        // Return the edit view with the Gudang data and cabangOptions
        return view('gudang.edit', compact('gudang', 'cabangOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fgudangid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fgudangcode' => "required|string|unique:msgudang,fgudangcode,{$fgudangid},fgudangid",
                'fgudangname' => 'required|string',
                'faddress' => 'required|string',
                'fcabangkode' => 'required|string', // Ensure the cabang code is validated and passed
            ],
            [
                'fgudangcode.unique' => 'Kode Gudang sudah ada.',
                'fgudangcode.required' => 'Kode Gudang harus diisi.',
                'fgudangname.required' => 'Nama Gudang harus diisi.',
                'faddress.required' => 'Alamat Gudang harus diisi.',
                'fcabangkode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        $gudang = Gudang::findOrFail($fgudangid);
        $gudang->update($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil di-update.');
    }

    public function destroy($fgudangid)
    {
        $gudang = Gudang::findOrFail($fgudangid);
        $gudang->delete();

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil dihapus.');
    }
}
