<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use Illuminate\Http\Request;

class SalesmanController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fsalesmancode', 'fsalesmanname'])
            ? $request->filter_by
            : 'fsalesmancode';

        $search = $request->search;

        $salesmen = Salesman::when($search, function ($q) use ($filterBy, $search) {
            $q->where($filterBy, 'ILIKE', '%' . $search . '%');
        })
            ->orderBy('fsalesmanid', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('salesman.index', compact('salesmen', 'filterBy', 'search'));
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

        $validated['fnonactive'] = '0';

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

        $validated['fnonactive'] = '0';
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
