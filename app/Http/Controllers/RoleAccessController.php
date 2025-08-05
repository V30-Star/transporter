<?php

namespace App\Http\Controllers;

use App\Models\Sysuser;
use App\Models\RoleAccess;
use Illuminate\Http\Request;

class RoleAccessController extends Controller
{
    public function index($fuid)
    {
        // Find the user by fuid
        $user = Sysuser::findOrFail($fuid);

        // Find the existing role access for the user by fuserid
        $roleAccess = RoleAccess::where('fuserid', $user->fuid)->first();

        // Pass the user and roleAccess data to the view
        return view('roleaccess.index', compact('user', 'roleAccess'));
    }

    // Function untuk menyimpan RESTRICTED akses role
    public function store(Request $request)
    {
        // Validate the input
        $request->validate([
            'fuid' => 'required|exists:sysuser,fuid',  // Ensure fuid exists in sysuser table
            'permission' => 'required|array',  // Permissions that should be restricted
        ]);

        // Find the user by fuid
        $user = Sysuser::findOrFail($request->fuid);

        // Combine selected restricted permissions into a comma-separated string
        $restrictedPermissions = implode(',', $request->permission);

        // Check if role access already exists for the user
        $roleAccess = RoleAccess::where('fuserid', $user->fuid)->first();

        if ($roleAccess) {
            // Update the existing role access
            $roleAccess->update([
                'fpermission' => $restrictedPermissions,  // Update the restricted permissions
            ]);
        } else {
            // Create new role access if it doesn't exist
            RoleAccess::create([
                'fuserid' => $user->fuid,  // Use fuid from sysuser as fuserid in msroleaccess
                'fpermission' => $restrictedPermissions,  // Save the restricted permissions
            ]);
        }

        // Redirect back after saving or updating
        return redirect()->route('roleaccess.index', ['fuid' => $request->fuid])
            ->with('success', 'Role Access Restriction berhasil disimpan.');
    }
}
