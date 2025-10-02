<?php

namespace App\Http\Controllers;

use App\Models\Subaccount;
use Illuminate\Http\Request;

class SubaccountController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fsubaccountcode', 'fsubaccountname', 'fsubaccountid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsubaccountid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $subaccounts = Subaccount::orderBy($sortBy, $sortDir)->get(['fsubaccountcode', 'fsubaccountname', 'fsubaccountid']);

        $canCreate = in_array('createSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSubAccount', explode(',', session('user_restricted_permissions', '')));

        return view('subaccount.index', compact('subaccounts', 'canCreate', 'canEdit', 'canDelete'));
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
