<?php

namespace App\Http\Controllers;

use App\Models\RoleAccess;
use App\Models\Sysuser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleAccessController extends Controller
{
    public function index($fuid)
    {
        // User target
        $user = Sysuser::findOrFail($fuid);

        // RoleAccess untuk user target
        $roleAccess = RoleAccess::where('fusercreate', $user->fuid)->first();

        // Kirim daftar user lain untuk dropdown "copy from user"
        // (kalau mau exclude diri sendiri, pakai ->where('fuid', '!=', $user->fuid))
        $allUsers = Sysuser::orderBy('fsysuserid')->get(['fuid', 'fsysuserid', 'fname']);

        return view('roleaccess.index', compact('user', 'roleAccess', 'allUsers'));
    }

    /** Daftar permission = checkbox yang tampil di form roleaccess.index. */
    private function knownPermissions(): array
    {
        static $known = null;

        if ($known === null) {
            $html = (string) @file_get_contents(resource_path('views/roleaccess/index.blade.php'));
            preg_match_all('/name="permission\[\]"\s+value="([^"]+)"/', $html, $matches);
            $known = array_values(array_unique($matches[1]));
        }

        return $known;
    }

    public function store(Request $request)
    {
        $request->validate([
            'fuid' => 'required|exists:sysuser,fuid',
            'permission' => 'nullable|array',
            'permission.*' => ['string', Rule::in($this->knownPermissions())],
        ], [
            'permission.*.in' => 'Permission tidak dikenal.',
        ]);

        $user = Sysuser::findOrFail($request->fuid);

        $restrictedPermissions = $request->has('permission') && is_array($request->permission)
            ? implode(',', $request->permission)
            : null;

        $roleAccess = RoleAccess::where('fusercreate', $user->fuid)->first();

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
                    'fusercreate' => $user->fuid,      // relasi ke Sysuser.fuid
                    'fpermission' => $restrictedPermissions,
                ]);
            }
        }

        $currentUser = auth('sysuser')->user() ?? auth()->user();
        if ($currentUser && (int) $currentUser->fuid === (int) $user->fuid) {
            session(['user_restricted_permissions' => $restrictedPermissions ?? '']);
        }

        return redirect()->route('roleaccess.index', ['fuid' => $request->fuid])
            ->with('success', 'Set menu berhasil disimpan.');
    }

    /**
     * Ambil daftar permission milik user sumber (JSON) untuk dipakai AJAX "Copy"
     */
    public function getPermissions(string $fuid)
    {
        // fuid = Sysuser.fuid
        $ra = RoleAccess::where('fusercreate', $fuid)->first();

        return response()->json([
            'permissions' => $ra && $ra->fpermission
                ? array_filter(array_map('trim', explode(',', $ra->fpermission)))
                : [],
        ]);
    }

    /**
     * Clone & Save: salin permission dari source_fuid ke target fuid (current page).
     */
    public function cloneToUser(Request $request)
    {
        $request->validate([
            'source_fuid' => 'required|exists:sysuser,fuid',
            'fuid' => 'required|exists:sysuser,fuid', // target
            'fusercreate' => 'required',                      // target fsysuserid (untuk disimpan bila perlu)
        ]);

        // Ambil permission dari sumber
        $source = RoleAccess::where('fusercreate', $request->source_fuid)->first();
        $permissions = $source?->fpermission ?? '';

        // Tulis/replace ke user target
        RoleAccess::updateOrCreate(
            ['fusercreate' => $request->fuid], // kunci unik role access = fusercreate (Sysuser.fuid)
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
