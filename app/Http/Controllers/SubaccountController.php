<?php

namespace App\Http\Controllers;

use App\Models\Subaccount;
use Illuminate\Http\Request;

class SubaccountController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fsubaccountcode', 'fsubaccountid', 'fsubaccountname'])
            ? $request->filter_by
            : 'fsubaccountcode';

        $search = $request->search;

        $subaccounts = Subaccount::when($search, function ($q) use ($filterBy, $search) {
            $q->where($filterBy, 'ILIKE', '%' . $search . '%');
        })
            ->orderBy('fsubaccountid', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('subaccount.index', compact('subaccounts', 'filterBy', 'search'));
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

        $validated['fnonactive'] = '0';

        // Create the new Subaccount
        Subaccount::create($validated);

        return redirect()
            ->route('subaccount.index')
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

        $validated['fnonactive'] = '0';
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
