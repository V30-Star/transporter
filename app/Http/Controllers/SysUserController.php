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
        $allowedSorts = ['fuid', 'fsysuserid', 'fname', 'created_at', 'fuserid', 'fcabang', 'sysuser.fsalesman', 'salesman_name'];
        $sortBy     = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsysuserid';
        $sortDir    = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        // Mendefinisikan kolom yang akan dipilih
        $selectColumns = [
            'sysuser.fuid',
            'sysuser.fsysuserid',
            'sysuser.fname',
            'sysuser.created_at',
            'sysuser.fuserid',
            'sysuser.fcabang',
            'sysuser.fsalesman',
            // Mengambil nama salesman, dan memberikan alias 'fsalesmanname' atau 'salesman_name'
            'mssalesman.fsalesmanname AS salesman_name',
        ];

        $sysusers = Sysuser::query()
            // Melakukan LEFT JOIN ke tabel mssalesman
            // Kunci join: sysuser.fsalesman = mssalesman.fsalesmanid
            ->leftJoin('mssalesman', 'sysuser.fsalesman', '=', 'mssalesman.fsalesmanid')

            // Memilih kolom secara spesifik
            ->select($selectColumns)

            // Menangani sorting
            ->orderBy($sortBy, $sortDir)

            // Mengambil hasilnya
            ->get();

        $perms         = explode(',', (string) session('user_restricted_permissions', ''));
        $canCreate     = in_array('createSysuser', $perms, true);
        $canEdit       = in_array('updateSysuser', $perms, true);
        $canDelete     = in_array('deleteSysuser', $perms, true);
        $canRoleAccess = in_array('roleaccess', $perms, true);

        return view('sysuser.index', compact('sysusers', 'canCreate', 'canEdit', 'canDelete'));
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

        $validated['fname'] = mb_strtoupper($validated['fname']);
        $validated['fsysuserid'] = mb_strtoupper($validated['fsysuserid']);

        $validated['fcabang'] = $request->fcabang ?? '-';
        $validated['fuserlevel'] = $validated['fuserlevel'] == 'Admin' ? '2' : '1';
        $validated['fuserid'] = auth('sysuser')->user()->fname ?? null;
        $validated['created_at'] = now();

        $validated['fsalesman'] = $request->has('fsalesman') ? $request->fsalesman : '-';  // Atau null jika memang diperlukan

        $validated['password'] = Hash::make($validated['password']);

        try {
            Sysuser::create($validated);
            return redirect()
                ->route('sysuser.create')
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

        $validated['fname'] = mb_strtoupper($validated['fname']);
        $validated['fsysuserid'] = mb_strtoupper($validated['fsysuserid']);

        $validated['fcabang'] = $request->fcabang ?? '-';
        $validated['fuserlevel'] = $validated['fuserlevel'] == 'Admin' ? '2' : '1';
        $validated['fuserid'] = auth('sysuser')->user()->fname ?? null;
        $validated['updated_at'] = now();
        $validated['fsalesman'] = $request->has('fsalesman') ? $request->fsalesman : '-';  // Atau null jika memang diperlukan

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
