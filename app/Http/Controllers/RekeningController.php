<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use Illuminate\Http\Request;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['frekeningcode', 'frekeningname', 'frekeningid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'frekeningid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Rekening::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $rekenings = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['frekeningcode', 'frekeningname', 'frekeningid', 'fnonactive']);

        $canCreate = in_array('createRekening', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateRekening', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteRekening', explode(',', session('user_restricted_permissions', '')));

        return view('rekening.index', compact('rekenings', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('rekening.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'frekeningname' => strtoupper($request->frekeningname),
        ]);

        // Validate incoming request data
        $validated = $request->validate(
            [
                'frekeningname' => 'required|string|unique:msrekening,frekeningname',
            ],
            [
                'frekeningname.required' => 'Nama rekening harus diisi.',
                'frekeningname.unique' => 'Nama Rekening ini sudah ada',
            ]
        );

        $validated['frekeningname'] = strtoupper($validated['frekeningname']);

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
        $request->merge([
            'frekeningname' => strtoupper($request->frekeningname),
        ]);

        $validated = $request->validate(
            [
                'frekeningname' => 'required|string|string|unique:msrekening,frekeningname',
            ],
            [
                'frekeningname.required' => 'Nama rekening harus diisi.',
                'frekeningname.unique' => 'Nama Rekening ini sudah ada',
            ]
        );
        
        $validated['frekeningname'] = strtoupper($validated['frekeningname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['frekeningcode'] = '0';
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
