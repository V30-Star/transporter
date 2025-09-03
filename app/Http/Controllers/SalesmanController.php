<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use Illuminate\Http\Request;

class SalesmanController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all'; // 'all' | 'fsalesmancode' | 'fsalesmanname'

        $salesmen = Salesman::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'fsalesmancode') {
                    $qq->where('fsalesmancode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'fsalesmanname') {
                    $qq->where('fsalesmanname', 'ILIKE', "%{$search}%");
                } else { // 'all'
                    $qq->where('fsalesmancode', 'ILIKE', "%{$search}%")
                        ->orWhere('fsalesmanname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy('fsalesmanid', 'desc')
            ->paginate(10)
            ->withQueryString();

        // permissions (samakan dengan konvensi app kamu)
        $canCreate = in_array('createSalesman', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSalesman', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSalesman', explode(',', session('user_restricted_permissions', '')));

        // AJAX response untuk live search/pagination
        if ($request->ajax()) {
            $rows = collect($salesmen->items())->map(function ($s) {
                return [
                    'fsalesmanid'   => $s->fsalesmanid,
                    'fsalesmancode' => $s->fsalesmancode,
                    'fsalesmanname' => $s->fsalesmanname,
                    // tambahkan kolom lain jika ditampilkan di tabel
                    'edit_url'      => route('salesman.edit', $s->fsalesmanid),
                    'destroy_url'   => route('salesman.destroy', $s->fsalesmanid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $salesmen->previousPageUrl(),
                    'next'         => $salesmen->nextPageUrl(),
                    'current_page' => $salesmen->currentPage(),
                    'last_page'    => $salesmen->lastPage(),
                ],
            ]);
        }

        // render awal
        return view('salesman.index', compact('salesmen', 'filterBy', 'search', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('salesman.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fsalesmancode' => 'required|string|unique:mssalesman,fsalesmancode',
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode Salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode Salesman sudah ada.',
                'fsalesmanname.required' => 'Nama Salesman wajib diisi.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Salesman
        Salesman::create($validated);

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil ditambahkan.');
    }

    public function edit($fsalesmanid)
    {
        // Ambil data berdasarkan PK fsalesmanid
        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.edit', compact('salesman'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fsalesmanid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fsalesmancode' => "required|string|unique:mssalesman,fsalesmancode,{$fsalesmanid},fsalesmanid",
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode Salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode Salesman sudah ada.',
                'fsalesmanname.required' => 'Nama Salesman wajib diisi.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        $salesman = Salesman::findOrFail($fsalesmanid);
        $salesman->update($validated);

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil di-update.');
    }

    public function destroy($fsalesmanid)
    {
        $salesman = Salesman::findOrFail($fsalesmanid);
        $salesman->delete();

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil dihapus.');
    }
}
