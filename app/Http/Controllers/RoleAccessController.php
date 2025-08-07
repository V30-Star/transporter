<?php

namespace App\Http\Controllers;

use App\Models\Sysuser;
use App\Models\RoleAccess;
use Illuminate\Http\Request;

class RoleAccessController extends Controller
{
    public function index($fuid)
    {
        $user = Sysuser::findOrFail($fuid);

        $roleAccess = RoleAccess::where('fuserid', $user->fuid)->first();

        return view('roleaccess.index', compact('user', 'roleAccess'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fuid' => 'required|exists:sysuser,fuid',
            'permission' => 'nullable|array',
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
                    'fuserid' => $user->fuid,
                    'fpermission' => $restrictedPermissions,
                ]);
            }
        }

        return redirect()->route('roleaccess.index', ['fuid' => $request->fuid])
            ->with('success', 'Set Menu berhasil disimpan.');
    }
}
