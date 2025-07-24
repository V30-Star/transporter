<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sysuser;
use Illuminate\Support\Facades\Hash;  

class SysUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $sysusers = Sysuser::when($search, function ($query, $search) {
            return $query->whereRaw('LOWER(fsysuserid) LIKE ?', ['%' . strtolower($search) . '%'])
                         ->orWhereRaw('LOWER(fname) LIKE ?', ['%' . strtolower($search) . '%'])
                         ->orWhereRaw('LOWER(fcabang) LIKE ?', ['%' . strtolower($search) . '%']);
        })->paginate(10);

        return view('sysuser.index', compact('sysusers', 'search'));
    }

    public function create()
    {
        return view('sysuser.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:100',
            'fsysuserid' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'fsalesman' => 'nullable|string|size:1', 
            'account_level' => 'required|string|in:User,Admin', 
        ]);

        $validated['fcabang'] = $request->fcabang ?? '-';
        $validated['fuserlevel'] = $validated['account_level'] == 'Admin' ? '2' : '1';
        $validated['fuserid'] = "User yang membuat";
        $validated['created_at'] = now(); 
        $validated['updated_at'] = now(); 
        
        $validated['fsalesman'] = $request->has('fsalesman') ? '1' : '0';
        
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

        // Pass the sysuser to the edit view
        return view('sysuser.edit', compact('sysuser'));
    }

    public function update(Request $request, $fuid)
    {
        // Validate incoming request
        $validated = $request->validate([
            'fsysuserid'    => 'string',
            'fname'         => 'required|string',
            'password'      => 'nullable|string|confirmed', 
            'fsalesman'     => 'nullable|string|size:1', // Nullable
            'fuserlevel'    => 'required|string|size:1',
            'fcabang'       => 'required|string',
        ]);

        // Find and update the sysuser
        $sysuser = Sysuser::findOrFail($fuid);

        // Only hash the password if it is filled
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // Remove password if not filled
        }

        // Set the updated_at field to the current timestamp
        $validated['updated_at'] = now();

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
