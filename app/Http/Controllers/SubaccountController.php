<?php

namespace App\Http\Controllers;

use App\Models\Subaccount;
use Illuminate\Http\Request;

class SubaccountController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fsubaccountcode', 'fsubaccountname', 'fsubaccountid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsubaccountid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Subaccount::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $subaccounts = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fsubaccountcode', 'fsubaccountname', 'fsubaccountid', 'fnonactive']);

        $canCreate = in_array('createSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSubAccount', explode(',', session('user_restricted_permissions', '')));

        return view('subaccount.index', compact('subaccounts', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('subaccount.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fsubaccountcode' => strtoupper($request->fsubaccountcode),
        ]);

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

        $validated['fsubaccountcode'] = strtoupper($validated['fsubaccountcode']);
        $validated['fsubaccountname'] = strtoupper($validated['fsubaccountname']);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
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

        return view('subaccount.edit', [
            'subaccount' => $subaccount,
            'action' => 'edit'
        ]);
    }

    public function view($fsubaccountid)
    {
        // Ambil data berdasarkan PK fsubaccountid
        $subaccount = Subaccount::findOrFail($fsubaccountid);

        return view('subaccount.view', [
            'subaccount' => $subaccount
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fsubaccountid)
    {
        $request->merge([
            'fsubaccountcode' => strtoupper($request->fsubaccountcode),
        ]);

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

        $validated['fsubaccountcode'] = strtoupper($validated['fsubaccountcode']);
        $validated['fsubaccountname'] = strtoupper($validated['fsubaccountname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        $subaccount = Subaccount::findOrFail($fsubaccountid);
        $subaccount->update($validated);

        return redirect()
            ->route('subaccount.index')
            ->with('success', 'Subaccount berhasil di-update.');
    }

    public function delete($fsubaccountid)
    {
        $subaccount = Subaccount::findOrFail($fsubaccountid);
        return view('subaccount.edit', [
            'subaccount' => $subaccount,
            'action' => 'delete'
        ]);
    }

    public function destroy($fsubaccountid)
    {
        try {
            $subaccount = Subaccount::findOrFail($fsubaccountid);
            $subaccount->delete();

            return redirect()->route('subaccount.index')->with('success', 'Data subaccount ' . $subaccount->fsubaccountname . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('subaccount.delete', $fsubaccountid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
