<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use Illuminate\Http\Request;

class SalesmanController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->search);

        $allowedFilters = ['fsalesmancode', 'fsalesmanname', 'fsalesmanid', 'all'];
        $filterBy = in_array($request->filter_by, $allowedFilters, true) ? $request->filter_by : 'all';

        $allowedSorts = ['fsalesmancode', 'fsalesmanname', 'fsalesmanid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsalesmanid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        // ✅ 1) Read from query if present, else from session, else default 'active'
        $status = $request->filled('status')
            ? (string) $request->status
            : (string) $request->session()->get('salesman.status', 'active');

        // ✅ 2) If AJAX brings a new status, store it to session
        if ($request->ajax() && $request->filled('status')) {
            $request->session()->put('salesman.status', (string) $request->status);
        }

        $salesmen = Salesman::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'fsalesmancode') {
                    $qq->where('fsalesmancode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'fsalesmanid') {
                    $qq->whereRaw('CAST(fsalesmanid AS TEXT) ILIKE ?', ["%{$search}%"]);
                } elseif ($filterBy === 'fsalesmanname') {
                    $qq->where('fsalesmanname', 'ILIKE', "%{$search}%");
                } else {
                    $qq->where('fsalesmancode', 'ILIKE', "%{$search}%")
                        ->orWhereRaw('CAST(fsalesmanid AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhere('fsalesmanname', 'ILIKE', "%{$search}%");
                }
            });
        })
            // 0 = Active, 1 = Non Active
            ->when($status === 'active',    fn($q) => $q->where('fnonactive', 0))
            ->when($status === 'nonactive', fn($q) => $q->where('fnonactive', 1))
            ->orderBy('fnonactive', 'asc')
            ->orderBy($sortBy, $sortDir)
            ->orderBy('fsalesmanid', 'desc')
            ->paginate(10)
            ->withQueryString();

        $permsStr  = (string) session('user_restricted_permissions', '');
        $permsArr  = explode(',', $permsStr);
        $canCreate = in_array('createSalesman', $permsArr, true);
        $canEdit   = in_array('updateSalesman', $permsArr, true);
        $canDelete = in_array('deleteSalesman', $permsArr, true);

        if ($request->ajax()) {
            $rows = collect($salesmen->items())->map(function ($s) {
                return [
                    'fsalesmanid'   => $s->fsalesmanid,
                    'fsalesmancode' => $s->fsalesmancode,
                    'fsalesmanname' => $s->fsalesmanname,
                    'fnonactive'    => $s->fnonactive, // 0/1
                    'status_label'  => $s->fnonactive == 1 ? 'Non Active' : 'Active',
                    'edit_url'      => route('salesman.edit', $s->fsalesmanid),
                    'destroy_url'   => route('salesman.destroy', $s->fsalesmanid),
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
                    'prev'         => $salesmen->previousPageUrl(),
                    'next'         => $salesmen->nextPageUrl(),
                    'current_page' => $salesmen->currentPage(),
                    'last_page'    => $salesmen->lastPage(),
                ],
                'sort' => [
                    'by'  => $sortBy,
                    'dir' => $sortDir,
                ],
                'filters' => [
                    'status' => $status, // ✅ echo back effective status
                ],
            ]);
        }

        return view('salesman.index', compact(
            'salesmen',
            'filterBy',
            'search',
            'canCreate',
            'canEdit',
            'canDelete',
            'sortBy',
            'sortDir',
            'status'
        ));
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
        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Salesman
        Salesman::create($validated);

        return redirect()
            ->route('salesman.create')
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

        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);
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

    public function destroy(Request $request, $fsalesmanid)
    {
        $salesman = Salesman::findOrFail($fsalesmanid);
        $salesman->delete();

        return redirect()
            ->route('salesman.index', $request->only(['status', 'search', 'filter_by', 'sort_by', 'sort_dir', 'page']))
            ->with('success', 'Salesman dihapus.');
    }
}
