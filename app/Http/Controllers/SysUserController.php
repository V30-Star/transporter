<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Salesman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sysuser;
use Illuminate\Support\Facades\Hash;

class SysUserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $sysusers = Sysuser::when($search !== '', function ($q) use ($search) {
            $q->where(function ($qq) use ($search) {
                $qq->where('fsysuserid', 'ILIKE', "%{$search}%")
                    ->orWhere('fname',      'ILIKE', "%{$search}%")
                    ->orWhere('fcabang',    'ILIKE', "%{$search}%");
            });
        })
            ->orderBy('fsysuserid') // atau field lain sesuai kebutuhan
            ->paginate(10)
            ->withQueryString();

        // Permissions (samakan dengan konvensi lain; sesuaikan jika naming berbeda di app kamu)
        $canCreate = in_array('createSysuser', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSysuser', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSysuser', explode(',', session('user_restricted_permissions', '')));
        $canRoleAccess = in_array('roleaccess', explode(',', session('user_restricted_permissions', '')));

        // Respon AJAX untuk live search/pagination
        if ($request->ajax()) {
            $rows = collect($sysusers->items())->map(function ($u) {
                return [
                    'fsysuserid'  => $u->fsysuserid,
                    'fname'       => $u->fname,
                    'created_at'  => optional($u->created_at)->format('Y-m-d H:i'),
                    'fuserid'     => $u->fuserid,
                    'fcabang'     => $u->fcabang,
                    'edit_url'    => route('sysuser.edit', $u->fuid),
                    'destroy_url' => route('sysuser.destroy', $u->fuid),
                    'can_role_access' => route('roleaccess.index', $u->fuid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete, 'can_role_access' => $canRoleAccess],
                'links' => [
                    'prev'         => $sysusers->previousPageUrl(),
                    'next'         => $sysusers->nextPageUrl(),
                    'current_page' => $sysusers->currentPage(),
                    'last_page'    => $sysusers->lastPage(),
                ],
            ]);
        }

        // Render awal (Blade)
        return view('sysuser.index', compact('sysusers', 'search', 'canCreate', 'canEdit', 'canDelete', 'canRoleAccess'));
    }

    public function create()
    {

        $salesman = Salesman::where('fnonactive', 0)->get();
        $cabangs = DB::table('mscabang')
            ->select('fcabangkode', 'fcabangname')
            ->orderBy('fcabangkode')
            ->get();

        return view('sysuser.create', compact('salesman', 'cabangs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:100',
            'fsysuserid' => 'required|string|unique:sysuser,fsysuserid',
            'password' => 'required|string|min:6|confirmed',
            'fsalesman' => 'nullable',
            'fuserlevel' => 'string|in:User,Admin',
            'fcabang' => 'string',
        ], [
            'fsysuserid.unique' => 'Username sudah digunakan, silakan pilih username lain.',
            'fname.required' => 'Nama harus diisi.',
            'fsysuserid.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
            'fuserlevel.required' => 'Level akun harus User atau Admin.',
            'fcabang.required' => 'Cabang harus diisi.',
        ]);

        $validated['fcabang'] = $request->fcabang ?? '-';
        $validated['fuserlevel'] = $validated['fuserlevel'] == 'Admin' ? '2' : '1';
        $validated['fuserid'] = auth('sysuser')->user()->fname ?? null;
        $validated['created_at'] = now();

        $validated['fsalesman'] = $request->fsalesman;

        $validated['password'] = Hash::make($validated['password']);

        try {
            Sysuser::create($validated);
            return redirect()
                ->route('sysuser.index')
                ->with('success', 'User berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan user: ' . $e->getMessage());
        }
    }

    public function edit($fuid)
    {
        // Find the sysuser by fuid (primary key)
        $sysuser = Sysuser::findOrFail($fuid);
        $salesman = Salesman::where('fnonactive', 0)->get();

        $cabangs = DB::table('mscabang')
            ->select('fcabangkode', 'fcabangname')
            ->orderBy('fcabangkode')
            ->get();

        // Pass the sysuser to the edit view
        return view('sysuser.edit', compact('sysuser', 'salesman', 'cabangs'));
    }

    public function update(Request $request, $fuid)
    {
        $validated = $request->validate([
            'fsysuserid' => 'required|string|unique:sysuser,fsysuserid,' . $fuid . ',fuid',
            'fname' => 'required|string',
            'password' => 'nullable|string|confirmed',
            'fsalesman' => 'nullable',
            'fuserlevel' => 'string|in:User,Admin',
            'fcabang' => 'string',
        ], [
            'fsysuserid.unique' => 'Username sudah digunakan, silakan pilih username lain.',
            'fname.required' => 'Nama harus diisi.',
            'fsysuserid.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
            'fuserlevel.required' => 'Level akun harus User atau Admin.',
            'fcabang.required' => 'Cabang harus diisi.',
        ]);


        // Find and update the sysuser
        $sysuser = Sysuser::findOrFail($fuid);

        // Only hash the password if it is filled
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // Remove password if not filled
        }
        $validated['fcabang'] = $request->fcabang ?? '-';
        $validated['fuserlevel'] = $validated['fuserlevel'] == 'Admin' ? '2' : '1';
        $validated['fuserid'] = auth('sysuser')->user()->fname ?? null;
        $validated['updated_at'] = now();
        $validated['fsalesman'] = $request->fsalesman;

        // Update the sysuser with the validated data
        $sysuser->update($validated);

        return redirect()
            ->route('sysuser.index')
            ->with('success', 'Sysuser berhasil diperbarui.');
    }

    public function destroy($fuid)
    {
        $sysuser = Sysuser::findOrFail($fuid);
        $sysuser->delete();

        return redirect()
            ->route('sysuser.index')
            ->with('success', 'Sysuser berhasil dihapus.');
    }
}
