<?php

namespace App\Http\Controllers;
use App\Models\Wilayah;    
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    // protected $permission = [];

    public function __construct()
    {
        // Load restricted permissions from session
        $restrictedPermissions = session('user_restricted_permissions', []);
        
        // Convert the permissions string into an array
        $this->restrictedPermission = $restrictedPermissions ? explode(',', $restrictedPermissions) : [];
    }

    public function index(Request $request)
    {
        // Check if the user is RESTRICTED from 'viewWilayah' permission
        // Jika user ADA di restricted list, maka TIDAK BISA akses
        if (in_array('viewWilayah', $this->restrictedPermission)) {
            return redirect('dashboard')->with('error', 'You do not have permission to view this page');
        }

        // Jika TIDAK ADA di restricted list, maka BISA akses
        // Continue with the regular logic for the 'Wilayah' page
        $filterBy = in_array($request->filter_by, ['fwilayahcode', 'fwilayahname'])
            ? $request->filter_by
            : 'fwilayahcode';

        $search = $request->search;

        $wilayahs = Wilayah::when($search, function($q) use ($filterBy, $search) {
            $q->where($filterBy, 'ILIKE', '%'.$search.'%');
        })
        ->orderBy('fwilayahid', 'desc')
        ->paginate(10)
        ->withQueryString();

        return view('master.wilayah.index', compact('wilayahs', 'filterBy', 'search'));
    }
    public function create()
    {
        if (in_array('createWilayah', $this->restrictedPermission)) {
            return redirect('dashboard')->with('error', 'You do not have permission to view this page');
        }

        return view('master.wilayah.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fwilayahcode' => 'required|string|unique:mswilayah,fwilayahcode',
            'fwilayahname' => 'required|string',
        ],[
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
        ]);

        // Add default values for the required fields
        $validated['fcreatedby'] = "User yang membuat"; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Use the current time
        $validated['fupdatedat'] = now(); // Use the current time

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Create the new Wilayah
        Wilayah::create($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function edit($fwilayahid)
    {
        if (in_array('updateWilayah', $this->restrictedPermission)) {
            return redirect('dashboard')->with('error', 'You do not have permission to view this page');
        }
        // Ambil data berdasarkan PK fwilayahid
        $wilayah = Wilayah::findOrFail($fwilayahid);

        return view('master.wilayah.edit', compact('wilayah'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fwilayahid)
    {
        // Validasi
        $validated = $request->validate([
            'fwilayahcode' => "required|string|unique:mswilayah,fwilayahcode,{$fwilayahid},fwilayahid",
            'fwilayahname' => 'required|string',
        ],[
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
        ]);

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Cari dan update
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->update($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil di-update.');
    }

    public function destroy($fwilayahid)
    {
        if (in_array('deleteWilayah', $this->restrictedPermission)) {
            return redirect('dashboard')->with('error', 'You do not have permission to view this page');
        }
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->delete();

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil dihapus.');
    }
}
