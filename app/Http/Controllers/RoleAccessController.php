<?php

namespace App\Http\Controllers;

use App\Models\Sysuser;
use App\Models\RoleAccess;
use Illuminate\Http\Request;

class RoleAccessController extends Controller
{
    public function index($fuid)
    {
        // User target
        $user = Sysuser::findOrFail($fuid);

        // RoleAccess untuk user target
        $roleAccess = RoleAccess::where('fuserid', $user->fuid)->first();

        // Kirim daftar user lain untuk dropdown "copy from user"
        // (kalau mau exclude diri sendiri, pakai ->where('fuid', '!=', $user->fuid))
        $allUsers = Sysuser::orderBy('fsysuserid')->get(['fuid', 'fsysuserid', 'fname']);

        return view('roleaccess.index', compact('user', 'roleAccess', 'allUsers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fuid'        => 'required|exists:sysuser,fuid',
            'permission'  => 'nullable|array',
        ]);

        $user = Sysuser::findOrFail($request->fuid);

        $restrictedPermissions = $request->has('permission') && is_array($request->permission)
            ? implode(',', $request->permission)
            : null;

        $roleAccess = RoleAccess::where('fuserid', $user->fuid)->first();

        if ($roleAccess) {
            if ($restrictedPermissions === null) {
                $roleAccess->delete();
            } else {
                $roleAccess->update([
                    'fpermission' => $restrictedPermissions,
                ]);
            }
        } else {
            if ($restrictedPermissions !== null) {
                RoleAccess::create([
                    'fuserid'     => $user->fuid,      // relasi ke Sysuser.fuid
                    'fpermission' => $restrictedPermissions,
                ]);
            }
        }

        return redirect()->route('roleaccess.index', ['fuid' => $request->fuid])
            ->with('success', 'Set Menu berhasil disimpan.');
    }

    /**
     * Ambil daftar permission milik user sumber (JSON) untuk dipakai AJAX "Copy"
     */
    public function getPermissions(string $fuid)
    {
        // fuid = Sysuser.fuid
        $ra = RoleAccess::where('fuserid', $fuid)->first();

        return response()->json([
            'permissions' => $ra && $ra->fpermission
                ? array_filter(array_map('trim', explode(',', $ra->fpermission)))
                : []
        ]);
    }

    /**
     * Clone & Save: salin permission dari source_fuid ke target fuid (current page).
     */
    public function cloneToUser(Request $request)
    {
        $request->validate([
            'source_fuid' => 'required|exists:sysuser,fuid',
            'fuid'        => 'required|exists:sysuser,fuid', // target
            'fuserid'     => 'required',                      // target fsysuserid (untuk disimpan bila perlu)
        ]);

        // Ambil permission dari sumber
        $source = RoleAccess::where('fuserid', $request->source_fuid)->first();
        $permissions = $source?->fpermission ?? '';

        // Tulis/replace ke user target
        RoleAccess::updateOrCreate(
            ['fuserid' => $request->fuid], // kunci unik role access = fuserid (Sysuser.fuid)
            [
                // simpan permission hasil clone, kosongkan jadi null jika string kosong
                'fpermission' => $permissions !== '' ? $permissions : null,
            ]
        );

        return redirect()
            ->route('roleaccess.index', ['fuid' => $request->fuid])
            ->with('success', 'Permissions berhasil diclone dari user sumber.');
    }
}
