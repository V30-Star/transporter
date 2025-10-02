<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use Illuminate\Http\Request;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['frekeningcode', 'frekeningname', 'frekeningid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'frekeningid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $rekenings = Rekening::orderBy($sortBy, $sortDir)->get(['frekeningcode', 'frekeningname', 'frekeningid']);

        $canCreate = in_array('createRekening', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateRekening', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteRekening', explode(',', session('user_restricted_permissions', '')));

        return view('rekening.index', compact('rekenings', 'canCreate', 'canEdit', 'canDelete'));
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
