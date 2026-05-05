<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use App\Models\Sysuser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SysUserController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fuid', 'fsysuserid', 'fname', 'created_at', 'fusercreate', 'fcabang', 'sysuser.fsalesman', 'salesman_name'];
        $sortBy = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsysuserid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $selectColumns = [
            'sysuser.fuid',
            'sysuser.fsysuserid',
            'sysuser.fname',
            'sysuser.created_at',
            'sysuser.fusercreate',
            'sysuser.fcabang',
            'sysuser.fsalesman',
            'mssalesman.fsalesmanname AS salesman_name',
        ];

        $sysusers = Sysuser::query()
            ->leftJoin('mssalesman', 'sysuser.fsalesman', '=', 'mssalesman.fsalesmanid')

            ->select($selectColumns)

            ->orderBy($sortBy, $sortDir)

            ->get();

        $perms = explode(',', (string) session('user_restricted_permissions', ''));
        $canCreate = in_array('createSysuser', $perms, true);
        $canEdit = in_array('updateSysuser', $perms, true);
        $canDelete = in_array('deleteSysuser', $perms, true);
        $canRoleAccess = in_array('roleaccess', $perms, true);

        return view('sysuser.index', compact('sysusers', 'canCreate', 'canEdit', 'canDelete', 'canRoleAccess'));
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
        $request->merge([
            'fsysuserid' => strtoupper($request->fsysuserid),
        ]);

        $validated = $request->validate([
            'fsysuserid' => 'required|string|unique:sysuser,fsysuserid',
            'fname' => 'required|string|max:100',
            'password' => 'required|string|min:6|confirmed',
            'fsalesman' => 'nullable',
            'fuserlevel' => 'string|in:User,Admin',
            'fcabang' => 'string',
        ], [
            'fsysuserid.unique' => 'Username sudah digunakan',
            'fname.required' => 'Nama harus diisi.',
            'fsysuserid.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
            'fuserlevel.required' => 'Level akun harus User atau Admin.',
            'fcabang.required' => 'Cabang harus diisi.',
        ]);
        // --- Pemrosesan Data ---
        $validated['fsysuserid'] = strtoupper($validated['fsysuserid']);
        $validated['fname'] = strtoupper($validated['fname']);

        $validated['fcabang'] = $request->fcabang ?? '-';
        $validated['fuserlevel'] = $validated['fuserlevel'] == 'Admin' ? '2' : '1';
        $validated['fusercreate'] = auth('sysuser')->user()->fname ?? null;
        $validated['created_at'] = now();

        // Pastikan ini menangani kasus jika fsalesman tidak dikirim sama sekali
        // CATATAN: Karena Anda mengubah default menjadi '0', ini akan menghindari error INTEGER jika 0 adalah ID Salesman yang valid.
        $fsalesmanValue = $request->fsalesman;

        if (empty($fsalesmanValue) || $fsalesmanValue === '-') {
            $validated['fsalesman'] = 0; // Menyimpan 0 ke kolom INTEGER
        } else {
            // Memastikan nilai yang ada adalah integer
            $validated['fsalesman'] = (int) $fsalesmanValue;
        }
        $validated['password'] = Hash::make($validated['password']);

        $finalData = $validated;
        unset($finalData['password']);

        try {
            Sysuser::create($validated);

            return redirect()
                ->route('sysuser.create')
                ->with('success', 'User berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan user: '.$e->getMessage());
        }
    }

    public function edit($fuid)
    {
        $sysuser = Sysuser::findOrFail($fuid);
        $salesman = Salesman::where('fnonactive', 0)->get();

        $cabangs = DB::table('mscabang')
            ->select('fcabangkode', 'fcabangname')
            ->orderBy('fcabangkode')
            ->get();

        return view('sysuser.edit', compact('sysuser', 'salesman', 'cabangs'));
    }

    public function update(Request $request, $fuid)
    {
        $request->merge([
            'fsysuserid' => strtoupper($request->fsysuserid),
        ]);

        $validated = $request->validate([
            'fsysuserid' => 'required|string|unique:sysuser,fsysuserid,'.$fuid.',fuid',
            'fname' => 'required|string',
            'password' => 'nullable|string|confirmed',
            'fsalesman' => 'nullable',
            'fuserlevel' => 'string|in:User,Admin',
            'fcabang' => 'string',
        ], [
            'fsysuserid.unique' => 'Username sudah digunakan',
            'fname.required' => 'Nama harus diisi.',
            'fsysuserid.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
            'fuserlevel.required' => 'Level akun harus User atau Admin.',
            'fcabang.required' => 'Cabang harus diisi.',
        ]);

        $validated['fsysuserid'] = strtoupper($validated['fsysuserid']);
        $validated['fname'] = strtoupper($validated['fname']);

        $sysuser = Sysuser::findOrFail($fuid);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['fname'] = mb_strtoupper($validated['fname']);
        $validated['fsysuserid'] = mb_strtoupper($validated['fsysuserid']);

        $validated['fcabang'] = $request->fcabang ?? '-';
        $validated['fuserlevel'] = $validated['fuserlevel'] == 'Admin' ? '2' : '1';
        $validated['fusercreate'] = auth('sysuser')->user()->fname ?? null;
        $validated['updated_at'] = now();
        $fsalesmanValue = $request->fsalesman;

        if (empty($fsalesmanValue) || $fsalesmanValue === '-') {
            $validated['fsalesman'] = 0;
        } else {
            $validated['fsalesman'] = (int) $fsalesmanValue;
        }
        $sysuser->update($validated);

        return redirect()
            ->route('sysuser.index')
            ->with('success', 'Sysuser berhasil diperbarui.');
    }

    public function delete($fuid)
    {
        $sysuser = Sysuser::with('salesman')->findOrFail($fuid);

        $relatedMessages = [];

        if (DB::table('roleaccess')->where('fuserid', $sysuser->fuid)->exists()) {
            $relatedMessages[] = 'Role Access';
        }

        return view('sysuser.delete', compact('sysuser', 'relatedMessages'));
    }

    public function destroy($fuid)
    {
        $sysuser = Sysuser::findOrFail($fuid);

        if (DB::table('roleaccess')->where('fuserid', $sysuser->fuid)->exists()) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak dapat dihapus karena sudah digunakan di Role Access.',
                ], 422);
            }

            return redirect()
                ->route('sysuser.delete', $fuid)
                ->with('error', 'User tidak dapat dihapus karena sudah digunakan di Role Access.');
        }

        $sysuser->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('sysuser.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
