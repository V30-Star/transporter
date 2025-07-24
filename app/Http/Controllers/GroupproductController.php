<?php

namespace App\Http\Controllers;

use App\Models\Groupproduct;
use Illuminate\Http\Request;

class GroupproductController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fgroupcode', 'fgroupname'])
            ? $request->filter_by
            : 'fgroupcode';

        $search = $request->search;

        $groupproducts = Groupproduct::when($search, function ($q) use ($filterBy, $search) {
            $q->where($filterBy, 'ILIKE', '%' . $search . '%');
        })
            ->orderBy('fgroupid', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('groupproduct.index', compact('groupproducts', 'filterBy', 'search'));
    }

    public function create()
    {
        return view('groupproduct.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fgroupcode' => 'required|string|unique:msgroupproduct,fgroupcode',
            'fgroupname' => 'required|string',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = "User yang membuat"; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Use the current time
        $validated['fupdatedat'] = now(); // Use the current time

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Create the new Groupproduct
        Groupproduct::create($validated);

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Groupproduct berhasil ditambahkan.');
    }

    public function edit($fgroupid)
    {
        // Fetch the Groupproduct data by its primary key
        $groupproduct = Groupproduct::findOrFail($fgroupid);

        return view('groupproduct.edit', compact('groupproduct'));
    }

    public function update(Request $request, $fgroupid)
    {
        // Validate the incoming data
        $validated = $request->validate([
            'fgroupcode' => "required|string|unique:msgroupproduct,fgroupcode,{$fgroupid},fgroupid",
            'fgroupname' => 'required|string',
        ]);

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Find and update the Groupproduct
        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $groupproduct->update($validated);

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Groupproduct berhasil di-update.');
    }

    public function destroy($fgroupid)
    {
        // Find and delete the Groupproduct
        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $groupproduct->delete();

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Groupproduct berhasil dihapus.');
    }
}
