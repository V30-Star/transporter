<?php

namespace App\Http\Controllers;

use App\Models\Groupcustomer;
use Illuminate\Http\Request;

class GroupcustomerController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fgroupcode', 'fgroupname', 'fgroupid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fgroupid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Groupcustomer::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $groupcustomers = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fgroupcode', 'fgroupname', 'fgroupid', 'fnonactive']);

        $canCreate = in_array('createGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupCustomer', explode(',', session('user_restricted_permissions', '')));

        return view('master.groupcustomer.index', compact('groupcustomers', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        // Menampilkan form untuk menambah grup customer baru
        return view('master.groupcustomer.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);
        // Validasi input yang diterima dari form
        $validated = $request->validate([
            'fgroupcode' => 'required|string|unique:msgroupcustomer,fgroupcode',
            'fgroupname' => 'required|string',
        ], [
            'fgroupcode.required' => 'Kode Group harus diisi.',
            'fgroupname.required' => 'Nama Group harus diisi.',
            'fgroupcode.unique' => 'Kode Group sudah digunakan',
        ]);

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        // Menambahkan nilai default untuk kolom yang tidak ada dalam form
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // bisa diganti dengan user yang sedang login
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Menyimpan data grup customer
        Groupcustomer::create($validated);

        // Mengarahkan kembali dengan pesan sukses
        return redirect()->route('groupcustomer.create')
            ->with('success', 'Group Customer berhasil ditambahkan.');
    }

    public function edit($fgroupid)
    {
        // Mengambil data grup customer berdasarkan ID
        $groupcustomer = Groupcustomer::findOrFail($fgroupid);

        // Menampilkan form untuk mengedit grup customer
        return view('master.groupcustomer.edit', [
            'groupcustomer' => $groupcustomer,
            'action' => 'edit'
        ]);
    }

    public function view($fgroupid)
    {
        // Mengambil data grup customer berdasarkan ID
        $groupcustomer = Groupcustomer::findOrFail($fgroupid);

        // Menampilkan form untuk mengedit grup customer
        return view('master.groupcustomer.view', [
            'groupcustomer' => $groupcustomer
        ]);
    }

    public function update(Request $request, $fgroupid)
    {
        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);

        $validated = $request->validate([
            'fgroupcode' => "required|string|unique:msgroupcustomer,fgroupcode,{$fgroupid},fgroupid",
            'fgroupname' => 'required|string',
        ], [
            'fgroupcode.required' => 'Kode Group harus diisi.',
            'fgroupname.required' => 'Nama Group harus diisi.',
            'fgroupcode.unique' => 'Kode Group sudah digunakan',
        ]);

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Mengambil data grup customer berdasarkan ID dan mengupdate
        $groupcustomer = Groupcustomer::findOrFail($fgroupid);
        $groupcustomer->update($validated);

        // Mengarahkan kembali dengan pesan sukses
        return redirect()->route('groupcustomer.index')
            ->with('success', 'Group Customer berhasil diupdate.');
    }

    public function delete($fgroupid)
    {
        $groupcustomer = Groupcustomer::findOrFail($fgroupid);
        return view('master.groupcustomer.edit', [
            'groupcustomer' => $groupcustomer,
            'action' => 'delete'
        ]);
    }

    public function destroy($fgroupid)
    {
        try {
            $groupcustomer = Groupcustomer::findOrFail($fgroupid);
            $groupcustomer->delete();

            return redirect()->route('groupcustomer.index')->with('success', 'Data groupcustomer ' . $groupcustomer->fgroupname . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('groupcustomer.delete', $fgroupid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
