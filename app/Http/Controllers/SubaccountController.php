<?php

namespace App\Http\Controllers;

use App\Models\Subaccount;
use Illuminate\Http\Request;

class SubaccountController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all'; // all | fsubaccountcode | fsubaccountid | fsubaccountname

        // Sorting
        $allowedSorts = ['fsubaccountcode', 'fsubaccountname', 'fsubaccountid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsubaccountid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $subaccounts = Subaccount::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'fsubaccountcode') {
                    $qq->where('fsubaccountcode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'fsubaccountid') {
                    $qq->whereRaw('CAST(fsubaccountid AS TEXT) ILIKE ?', ["%{$search}%"]);
                } elseif ($filterBy === 'fsubaccountname') {
                    $qq->where('fsubaccountname', 'ILIKE', "%{$search}%");
                } else { // all
                    $qq->where('fsubaccountcode', 'ILIKE', "%{$search}%")
                        ->orWhereRaw('CAST(fsubaccountid AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhere('fsubaccountname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('fsubaccountid', 'desc') // tie-breaker
            ->paginate(10)
            ->withQueryString();

        // permission flags
        $canCreate = in_array('createSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSubAccount', explode(',', session('user_restricted_permissions', '')));

        // AJAX response
        if ($request->ajax()) {
            $rows = collect($subaccounts->items())->map(function ($s) {
                return [
                    'fsubaccountid'   => $s->fsubaccountid,
                    'fsubaccountcode' => $s->fsubaccountcode,
                    'fsubaccountname' => $s->fsubaccountname,
                    'edit_url'        => route('subaccount.edit', $s->fsubaccountid),
                    'destroy_url'     => route('subaccount.destroy', $s->fsubaccountid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $subaccounts->previousPageUrl(),
                    'next'         => $subaccounts->nextPageUrl(),
                    'current_page' => $subaccounts->currentPage(),
                    'last_page'    => $subaccounts->lastPage(),
                ],
                'sort' => ['by' => $sortBy, 'dir' => $sortDir], // untuk ikon & state front-end
            ]);
        }

        return view('subaccount.index', compact(
            'subaccounts',
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
        return view('subaccount.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'fsubaccountcode' => 'required|string|unique:mssubaccount,fsubaccountcode',
                'fsubaccountname' => 'required|string',
            ],
            [
                'fsubaccountcode.required' => 'Kode subaccount harus diisi.',
                'fsubaccountname.required' => 'Nama subaccount harus diisi.',
                'fsubaccountcode.unique' => 'Kode subaccount sudah ada.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Subaccount
        Subaccount::create($validated);

        return redirect()
            ->route('subaccount.create')
            ->with('success', 'Subaccount berhasil ditambahkan.');
    }

    public function edit($fsubaccountid)
    {
        // Ambil data berdasarkan PK fsubaccountid
        $subaccount = Subaccount::findOrFail($fsubaccountid);

        return view('subaccount.edit', compact('subaccount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fsubaccountid)
    {
        // Validasi
        $validated = $request->validate(
            [
                'fsubaccountcode' => "required|string|unique:mssubaccount,fsubaccountcode,{$fsubaccountid},fsubaccountid",
                'fsubaccountname' => 'required|string',
            ],
            [
                'fsubaccountcode.required' => 'Kode subaccount harus diisi.',
                'fsubaccountname.required' => 'Nama subaccount harus diisi.',
                'fsubaccountcode.unique' => 'Kode subaccount sudah ada.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        $subaccount = Subaccount::findOrFail($fsubaccountid);
        $subaccount->update($validated);

        return redirect()
            ->route('subaccount.index')
            ->with('success', 'Subaccount berhasil di-update.');
    }

    public function destroy($fsubaccountid)
    {
        $subaccount = Subaccount::findOrFail($fsubaccountid);
        $subaccount->delete();

        return redirect()
            ->route('subaccount.index')
            ->with('success', 'Subaccount berhasil dihapus.');
    }
}
