<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    // protected $permission = [];

    public function __construct()
    {
        $restrictedPermissions = session('user_restricted_permissions', []);

        $this->restrictedPermission = $restrictedPermissions ? explode(',', $restrictedPermissions) : [];
    }

    public function index(Request $request)
    {
        $allowedSorts = ['fwilayahcode', 'fwilayahname', 'fwilayahid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fwilayahid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Wilayah::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $wilayahs = Wilayah::orderBy($sortBy, $sortDir)->get(['fwilayahcode', 'fwilayahname', 'fwilayahid', 'fnonactive']);

        $canCreate = in_array('createWilayah', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateWilayah', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteWilayah', explode(',', session('user_restricted_permissions', '')));

        return view('master.wilayah.index', compact('wilayahs', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('master.wilayah.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fwilayahcode' => strtoupper($request->fwilayahcode),
            'fwilayahname' => strtoupper($request->fwilayahname),
        ]);

        $validated = $request->validate([
            'fwilayahcode' => 'required|string|unique:mswilayah,fwilayahcode',
            'fwilayahname' => 'required|string|unique:mswilayah,fwilayahname',
        ], [
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
            'fwilayahname.unique' => 'Nama wilayah sudah ada, silakan gunakan nama lain.',
        ]);

        // Add default values for the required fields
        $validated['fwilayahcode'] = strtoupper($validated['fsalesmancode']);
        $validated['fwilayahname'] = strtoupper($validated['fsalesmanname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Wilayah::create($validated);

        return redirect()
            ->route('wilayah.create')
            ->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function edit($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);

        return view('master.wilayah.edit', compact('wilayah'));
    }

    public function update(Request $request, $fwilayahid)
    {
        $request->merge([
            'fwilayahcode' => strtoupper($request->fwilayahcode),
            'fwilayahname' => strtoupper($request->fwilayahname),
        ]);

        $validated = $request->validate([
            'fwilayahcode' => "required|string|unique:mswilayah,fwilayahcode,{$fwilayahid},fwilayahid",
            'fwilayahname' => 'required|string|unique:mswilayah,fwilayahname',
        ], [
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
            'fwilayahname.unique' => 'Nama wilayah sudah ada, silakan gunakan nama lain.',
        ]);

        // Add default values for the required fields
        $validated['fwilayahcode'] = strtoupper($validated['fsalesmancode']);
        $validated['fwilayahname'] = strtoupper($validated['fsalesmanname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->update($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil di-update.');
    }

    public function destroy($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);
        $wilayah->delete();

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil dihapus.');
    }
}
