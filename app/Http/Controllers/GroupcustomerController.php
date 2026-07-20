<?php

namespace App\Http\Controllers;

use App\Models\Groupcustomer;
use Illuminate\Http\Request;

class GroupcustomerController extends Controller
{
    private function ensureGroupCustomerPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu group customer.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureGroupCustomerPermission('viewGroupCustomer')) {
            return $guard;
        }

        $groupcustomers = Groupcustomer::orderBy('fgroupcode', 'asc')
            ->get(['fgroupcode', 'fgroupname', 'fgroupid', 'fnonactive']);

        $canCreate = in_array('createGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupCustomer', explode(',', session('user_restricted_permissions', '')));

        return view('groupcustomer.index', compact('groupcustomers', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureGroupCustomerPermission('createGroupCustomer')) {
            return $guard;
        }

        return view('groupcustomer.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureGroupCustomerPermission('createGroupCustomer')) {
            return $guard;
        }

        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);
        // Validasi input yang diterima dari form
        $validated = $request->validate([
            'fgroupcode' => 'required|string|unique:msgroupcustomer,fgroupcode',
            'fgroupname' => 'required|string',
        ], [
            'fgroupcode.required' => 'Kode group wajib diisi.',
            'fgroupname.required' => 'Nama group wajib diisi.',
            'fgroupcode.unique' => 'Kode group sudah ada.',
        ]);

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        // Menambahkan nilai default untuk kolom yang tidak ada dalam form
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // bisa diganti dengan user yang sedang login
        $validated['fcreatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';

        // Menyimpan data grup customer
        Groupcustomer::create($validated);

        // Mengarahkan kembali dengan pesan sukses
        return redirect()->route('groupcustomer.create')
            ->with('success', 'Group customer berhasil disimpan.');
    }

    public function edit($fgroupid)
    {
        if ($guard = $this->ensureGroupCustomerPermission('updateGroupCustomer')) {
            return $guard;
        }

        $groupcustomer = Groupcustomer::findOrFail($fgroupid);

        return view('groupcustomer.edit', [
            'groupcustomer' => $groupcustomer,
            'action' => 'edit',
        ]);
    }

    public function view($fgroupid)
    {
        if ($guard = $this->ensureGroupCustomerPermission('viewGroupCustomer')) {
            return $guard;
        }

        $groupcustomer = Groupcustomer::findOrFail($fgroupid);

        return view('groupcustomer.view', [
            'groupcustomer' => $groupcustomer,
        ]);
    }

    public function update(Request $request, $fgroupid)
    {
        if ($guard = $this->ensureGroupCustomerPermission('updateGroupCustomer')) {
            return $guard;
        }

        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);

        $validated = $request->validate([
            'fgroupcode' => "required|string|unique:msgroupcustomer,fgroupcode,{$fgroupid},fgroupid",
            'fgroupname' => 'required|string',
        ], [
            'fgroupcode.required' => 'Kode group wajib diisi.',
            'fgroupname.required' => 'Nama group wajib diisi.',
            'fgroupcode.unique' => 'Kode group sudah ada.',
        ]);

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        $userLogin = auth('sysuser')->user();
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';

        $groupcustomer = Groupcustomer::findOrFail($fgroupid);
        $groupcustomer->update($validated);

        // 2. Selalu INSERT log baru (feditmode = 'U')
        \Illuminate\Support\Facades\DB::table('logmsgroupcustomer')->insert([
            'fgroupid'     => $groupcustomer->fgroupid,
            'fgroupcode'   => $groupcustomer->fgroupcode,
            'fgroupname'   => $groupcustomer->fgroupname,
            'fcreatedat'   => $groupcustomer->fcreatedat,
            'fupdatedat'   => $groupcustomer->fupdatedat,
            'fcreatedby'   => $groupcustomer->fcreatedby,
            'fupdatedby'   => $groupcustomer->fupdatedby,
            'fnonactive'   => $groupcustomer->fnonactive,
            'feditmode'    => 'U', // Update
            'fuseridlog'   => $userLogin->fname ?? null,
            'fdatetimelog' => now(),
        ]);

        return redirect()->route('groupcustomer.index')
            ->with('success', 'Group customer berhasil diupdate.');
    }

    public function delete($fgroupid)
    {
        if ($guard = $this->ensureGroupCustomerPermission('deleteGroupCustomer')) {
            return $guard;
        }

        $groupcustomer = Groupcustomer::findOrFail($fgroupid);

        return view('groupcustomer.delete', [
            'groupcustomer' => $groupcustomer,
        ]);
    }

    public function destroy($fgroupid)
    {
        if (! $this->hasRestrictedPermission('deleteGroupCustomer')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu group customer.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu group customer.');
        }

        try {
            $groupcustomer = Groupcustomer::findOrFail($fgroupid);

            if (\Illuminate\Support\Facades\DB::table('mscustomer')->where('fgroup', $groupcustomer->fgroupid)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group customer tidak bisa dihapus. Sudah direferensi di customer.',
                ], 422);
            }

            $userLogin = auth('sysuser')->user();

            // 1. Selalu INSERT log baru sebelum data utama di-delete (feditmode = 'D')
            \Illuminate\Support\Facades\DB::table('logmsgroupcustomer')->insert([
                'fgroupid'     => $groupcustomer->fgroupid,
                'fgroupcode'   => $groupcustomer->fgroupcode,
                'fgroupname'   => $groupcustomer->fgroupname,
                'fcreatedat'   => $groupcustomer->fcreatedat,
                'fupdatedat'   => $groupcustomer->fupdatedat,
                'fcreatedby'   => $groupcustomer->fcreatedby,
                'fupdatedby'   => $groupcustomer->fupdatedby,
                'fnonactive'   => $groupcustomer->fnonactive,
                'feditmode'    => 'D', // Delete
                'fuseridlog'   => $userLogin->fname ?? null,
                'fdatetimelog' => now(),
            ]);

            $groupcustomer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Group customer ' . $groupcustomer->fgroupname . ' berhasil dihapus.',
                'redirect' => route('groupcustomer.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Group customer belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }
}
