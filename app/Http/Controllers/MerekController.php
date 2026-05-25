<?php

namespace App\Http\Controllers;

use App\Models\Merek;
use Illuminate\Http\Request;

class MerekController extends Controller
{
    private function ensureMerekPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu merek.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureMerekPermission('viewMerek')) {
            return $guard;
        }

        $mereks = Merek::orderBy('fmerekcode', 'asc')
            ->get(['fmerekid', 'fmerekcode', 'fmerekname', 'fnonactive']);

        $permsStr = (string) session('user_restricted_permissions', '');
        $permsArr = explode(',', $permsStr);
        $canCreate = in_array('createMerek', $permsArr, true);
        $canEdit = in_array('updateMerek', $permsArr, true);
        $canDelete = in_array('deleteMerek', $permsArr, true);

        return view('merek.index', compact('mereks', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureMerekPermission('createMerek')) {
            return $guard;
        }

        return view('merek.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureMerekPermission('createMerek')) {
            return $guard;
        }

        $request->merge([
            'fmerekcode' => strtoupper($request->fmerekcode),
        ]);

        $validated = $request->validate(
            [
                'fmerekcode' => 'required|string|unique:msmerek,fmerekcode',
                'fmerekname' => 'required|string',
            ],
            [
                'fmerekcode.required' => 'Kode merek wajib diisi.',
                'fmerekname.required' => 'Nama merek wajib diisi.',
                'fmerekcode.unique' => 'Kode merek sudah ada.',
            ]
        );

        $validated['fmerekcode'] = strtoupper($validated['fmerekcode']);
        $validated['fmerekname'] = strtoupper($validated['fmerekname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->input('fnonactive', 0) == 1 ? '1' : '0';

        $merek = Merek::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'id' => $merek->fmerekid,
                'code' => $merek->fmerekcode,
                'name' => $merek->fmerekname,
            ]);
        }

        return redirect()
            ->route('merek.create')
            ->with('success', 'Merek berhasil disimpan.');
    }

    public function edit($fmerekid)
    {
        if ($guard = $this->ensureMerekPermission('updateMerek')) {
            return $guard;
        }

        $merek = Merek::findOrFail($fmerekid);

        return view('merek.edit', [
            'merek' => $merek,
            'action' => 'edit',
        ]);
    }

    public function view($fmerekid)
    {
        if ($guard = $this->ensureMerekPermission('viewMerek')) {
            return $guard;
        }

        $merek = Merek::findOrFail($fmerekid);

        return view('merek.view', [
            'merek' => $merek,
        ]);
    }

    public function update(Request $request, $fmerekid)
    {
        if ($guard = $this->ensureMerekPermission('updateMerek')) {
            return $guard;
        }

        $request->merge([
            'fmerekcode' => strtoupper($request->fmerekcode),
        ]);

        $validated = $request->validate(
            [
                'fmerekcode' => "required|string|unique:msmerek,fmerekcode,{$fmerekid},fmerekid",
                'fmerekname' => 'required|string',
            ],
            [
                'fmerekcode.required' => 'Kode merek wajib diisi.',
                'fmerekname.required' => 'Nama merek wajib diisi.',
                'fmerekcode.unique' => 'Kode merek sudah ada.',
            ]
        );

        $validated['fmerekcode'] = strtoupper($validated['fmerekcode']);
        $validated['fmerekname'] = strtoupper($validated['fmerekname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $merek = Merek::findOrFail($fmerekid);
        $merek->update($validated);

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil diupdate.');
    }

    public function delete($fmerekid)
    {
        if ($guard = $this->ensureMerekPermission('deleteMerek')) {
            return $guard;
        }

        $merek = Merek::findOrFail($fmerekid);

        return view('merek.delete', [
            'merek' => $merek,
        ]);
    }

    public function destroy($fmerekid)
    {
        if (! $this->hasRestrictedPermission('deleteMerek')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu merek.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu merek.');
        }

        try {
            $merek = Merek::findOrFail($fmerekid);

            if (\Illuminate\Support\Facades\DB::table('msprd')->where('fmerek', $merek->fmerekid)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merek tidak bisa dihapus. Sudah direferensi di produk.',
                ], 422);
            }

            $merek->delete();

            return response()->json([
                'success' => true,
                'message' => 'Merek '.$merek->fmerekname.' berhasil dihapus.',
                'redirect' => route('merek.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Merek belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        if ($guard = $this->ensureMerekPermission('viewMerek')) {
            return $guard;
        }

        $query = Merek::query();

        $recordsTotal = Merek::count();

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fmerekcode', 'ilike', "%{$search}%")
                    ->orWhere('fmerekname', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fmerekname');
        $orderDir = $request->input('order_dir', 'asc');

        $allowedColumns = ['fmerekcode', 'fmerekname'];

        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fmerekname', 'asc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }
}
