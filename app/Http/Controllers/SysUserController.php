<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Salesman;
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

        $salesman = Salesman::where('fnonactive', 0)->get();

        return view('sysuser.create', compact('salesman'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:100',
            'fsysuserid' => 'required|string|unique:sysuser,fsysuserid',
            'password' => 'required|string|min:6|confirmed',
            'fsalesman' => 'nullable',
            'fuserlevel' => 'required|string|in:User,Admin',
            'fcabang' => 'required|string',
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

        // Pass the sysuser to the edit view
        return view('sysuser.edit', compact('sysuser', 'salesman'));
    }

    public function update(Request $request, $fuid)
    {
        $validated = $request->validate([
            'fsysuserid' => 'required|string|unique:sysuser,fsysuserid,' . $fuid . ',fuid',
            'fname' => 'required|string',
            'password' => 'nullable|string|confirmed',
            'fsalesman' => 'nullable',
            'fuserlevel' => 'required|string|size:1',
            'fcabang' => 'required|string',
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

        $validated['fuserid'] = auth('sysuser')->user()->fname ?? null;
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
