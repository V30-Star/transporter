<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use Illuminate\Http\Request;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        $rekenings = Rekening::orderBy('frekeningname', 'asc')
            ->get(['frekeningcode', 'frekeningname', 'frekeningid', 'fnonactive']);

        $canCreate = in_array('createRekening', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateRekening', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteRekening', explode(',', session('user_restricted_permissions', '')));

        return view('rekening.index', compact('rekenings', 'canCreate', 'canEdit', 'canDelete'));
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

        return view('rekening.edit', [
            'rekening' => $rekening,
            'action' => 'edit',
        ]);
    }

    public function view($frekeningid)
    {
        // Find Rekening by primary key
        $rekening = Rekening::findOrFail($frekeningid);

        return view('rekening.view', [
            'rekening' => $rekening,
        ]);
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

    public function delete($frekeningid)
    {
        $rekening = Rekening::findOrFail($frekeningid);

        return view('rekening.delete', [
            'rekening' => $rekening,
        ]);
    }

    public function destroy($frekeningid)
    {
        try {
            $rekening = Rekening::findOrFail($frekeningid);

            $rekening->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data rekening '.$rekening->frekeningname.' berhasil dihapus.',
                'redirect' => route('rekening.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: '.$e->getMessage(),
            ], 500);
        }
    }
}
