<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);

        // filter kolom (biar tetap kompatibel: fsatuancode | fsatuanname | fsatuanid | all)
        $allowedFilters = ['fsatuancode', 'fsatuanname', 'fsatuanid', 'all'];
        $filterBy = in_array($request->filter_by, $allowedFilters, true)
            ? $request->filter_by
            : 'all';

        // sorting: hanya izinkan kolom berikut; default pakai fsatuanid
        $allowedSorts = ['fsatuancode', 'fsatuanname', 'fsatuanid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsatuanid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $satuans = Satuan::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'fsatuancode') {
                    $qq->where('fsatuancode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'fsatuanid') {
                    $qq->whereRaw('CAST(fsatuanid AS TEXT) ILIKE ?', ["%{$search}%"]);
                } elseif ($filterBy === 'fsatuanname') {
                    $qq->where('fsatuanname', 'ILIKE', "%{$search}%");
                } else { // 'all'
                    $qq->where('fsatuancode', 'ILIKE', "%{$search}%")
                        ->orWhereRaw('CAST(fsatuanid AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhere('fsatuanname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('fsatuanid', 'desc')
            ->paginate(10)
            ->withQueryString();

        $permsStr  = (string) session('user_restricted_permissions', '');
        $permsArr  = explode(',', $permsStr);
        $canCreate = in_array('createSatuan', $permsArr, true);
        $canEdit   = in_array('updateSatuan', $permsArr, true);
        $canDelete = in_array('deleteSatuan', $permsArr, true);

        // AJAX response
        if ($request->ajax()) {
            $rows = collect($satuans->items())->map(function ($s) {
                return [
                    'fsatuanid'   => $s->fsatuanid,
                    'fsatuancode' => $s->fsatuancode,
                    'fsatuanname' => $s->fsatuanname,
                    'edit_url'    => route('satuan.edit', $s->fsatuanid),
                    'destroy_url' => route('satuan.destroy', $s->fsatuanid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => [
                    'can_create' => $canCreate,
                    'can_edit'   => $canEdit,
                    'can_delete' => $canDelete,
                ],
                'links' => [
                    'prev'         => $satuans->previousPageUrl(),
                    'next'         => $satuans->nextPageUrl(),
                    'current_page' => $satuans->currentPage(),
                    'last_page'    => $satuans->lastPage(),
                ],
                'sort' => [
                    'by'  => $sortBy,
                    'dir' => $sortDir,
                ],
            ]);
        }

        // render awal
        return view('satuan.index', compact(
            'satuans',
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
        return view('satuan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fsatuancode' => 'required|string|unique:mssatuan,fsatuancode',
                'fsatuanname' => 'required|string',
            ],
            [
                'fsatuancode.unique' => 'Kode Satuan sudah ada.',
                'fsatuancode.required' => 'Kode Satuan harus diisi.',
                'fsatuanname.required' => 'Nama Satuan harus diisi.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Satuan
        Satuan::create($validated);

        return redirect()
            ->route('satuan.create')
            ->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function edit($fsatuanid)
    {
        // Ambil data berdasarkan PK fsatuanid
        $satuan = Satuan::findOrFail($fsatuanid);

        return view('satuan.edit', compact('satuan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fsatuanid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fsatuancode' => "required|string|unique:mssatuan,fsatuancode,{$fsatuanid},fsatuanid",
                'fsatuanname' => 'required|string',
            ],
            [
                'fsatuancode.unique' => 'Kode Satuan sudah ada.',
                'fsatuancode.required' => 'Kode Satuan harus diisi.',
                'fsatuanname.required' => 'Nama Satuan harus diisi.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        // Cari dan update
        $satuan = Satuan::findOrFail($fsatuanid);
        $satuan->update($validated);

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil di-update.');
    }

    public function destroy($fsatuanid)
    {
        $satuan = Satuan::findOrFail($fsatuanid);
        $satuan->delete();

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil dihapus.');
    }
}
