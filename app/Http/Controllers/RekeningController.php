<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use Illuminate\Http\Request;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all'; // all | frekeningcode | frekeningid | frekeningname

        // Sorting
        $allowedSorts = ['frekeningcode', 'frekeningname', 'frekeningid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'frekeningid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $rekening = Rekening::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'frekeningcode') {
                    $qq->where('frekeningcode', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'frekeningid') {
                    $qq->whereRaw('CAST(frekeningid AS TEXT) ILIKE ?', ["%{$search}%"]);
                } elseif ($filterBy === 'frekeningname') {
                    $qq->where('frekeningname', 'ILIKE', "%{$search}%");
                } else { // all
                    $qq->where('frekeningcode', 'ILIKE', "%{$search}%")
                        ->orWhereRaw('CAST(frekeningid AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhere('frekeningname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('frekeningid', 'desc') // tie-breaker
            ->paginate(10)
            ->withQueryString();

        // permission flags
        $canCreate = in_array('createRekening', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateRekening', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteRekening', explode(',', session('user_restricted_permissions', '')));

        // AJAX response
        if ($request->ajax()) {
            $rows = collect($rekening->items())->map(function ($r) {
                return [
                    'frekeningid'   => $r->frekeningid,
                    'frekeningcode' => $r->frekeningcode,
                    'frekeningname' => $r->frekeningname,
                    'edit_url'      => route('rekening.edit', $r->frekeningid),
                    'destroy_url'   => route('rekening.destroy', $r->frekeningid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $rekening->previousPageUrl(),
                    'next'         => $rekening->nextPageUrl(),
                    'current_page' => $rekening->currentPage(),
                    'last_page'    => $rekening->lastPage(),
                ],
                'sort' => ['by' => $sortBy, 'dir' => $sortDir], // penting untuk front-end
            ]);
        }

        return view('rekening.index', compact(
            'rekening',
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
        return view('rekening.create');
    }

    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate(
            [
                'frekeningcode' => 'required|string|unique:msrekening,frekeningcode',
                'frekeningname' => 'required|string',
            ],
            [
                'frekeningcode.required' => 'Kode rekening harus diisi.',
                'frekeningname.required' => 'Nama rekening harus diisi.',
                'frekeningcode.unique' => 'Kode rekening sudah ada.',
            ]
        );

        // Add default values for created and updated fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Or use the authenticated user's name
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Set current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create new Rekening record
        Rekening::create($validated);

        return redirect()
            ->route('rekening.create')
            ->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function edit($frekeningid)
    {
        // Find Rekening by primary key
        $rekening = Rekening::findOrFail($frekeningid);

        return view('rekening.edit', compact('rekening'));
    }

    public function update(Request $request, $frekeningid)
    {
        // Validate incoming request data
        $validated = $request->validate(
            [
                'frekeningcode' => "required|string|unique:msrekening,frekeningcode,{$frekeningid},frekeningid",
                'frekeningname' => 'required|string',
            ],
            [
                'frekeningcode.required' => 'Kode rekening harus diisi.',
                'frekeningname.required' => 'Nama rekening harus diisi.',
                'frekeningcode.unique' => 'Kode rekening sudah ada.',
            ]
        );

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Or use the authenticated user's name
        $validated['fupdatedat'] = now(); // Set current time

        // Find Rekening and update
        $rekening = Rekening::findOrFail($frekeningid);
        $rekening->update($validated);

        return redirect()
            ->route('rekening.index')
            ->with('success', 'Rekening berhasil di-update.');
    }

    public function destroy($frekeningid)
    {
        // Find Rekening and delete it
        $rekening = Rekening::findOrFail($frekeningid);
        $rekening->delete();

        return redirect()
            ->route('rekening.index')
            ->with('success', 'Rekening berhasil dihapus.');
    }
}
