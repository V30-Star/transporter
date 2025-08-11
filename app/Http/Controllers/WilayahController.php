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
        $filterBy = in_array($request->filter_by, ['fwilayahcode', 'fwilayahname'])
            ? $request->filter_by
            : 'fwilayahcode';

        $search = $request->search;

        $wilayahs = Wilayah::when($search, function ($q) use ($filterBy, $search) {
            $q->where($filterBy, 'ILIKE', '%' . $search . '%');
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
        ], [
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
        ]);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = '0';

        Wilayah::create($validated);

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function edit($fwilayahid)
    {
        $wilayah = Wilayah::findOrFail($fwilayahid);

        return view('master.wilayah.edit', compact('wilayah'));
    }

    public function update(Request $request, $fwilayahid)
    {
        $validated = $request->validate([
            'fwilayahcode' => "required|string|unique:mswilayah,fwilayahcode,{$fwilayahid},fwilayahid",
            'fwilayahname' => 'required|string',
        ], [
            'fwilayahcode.required' => 'Kode wilayah harus diisi.',
            'fwilayahname.required' => 'Nama wilayah harus diisi.',
            'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
        ]);

        $validated['fnonactive'] = '0';
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
