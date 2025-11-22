<?php

namespace App\Http\Controllers;

use App\Models\Merek;
use Illuminate\Http\Request;

class MerekController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fmerekcode', 'fmerekname', 'fmerekid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fmerekid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Merek::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $mereks = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fmerekid', 'fmerekcode', 'fmerekname', 'fnonactive']);

        $permsStr  = (string) session('user_restricted_permissions', '');
        $permsArr  = explode(',', $permsStr);
        $canCreate = in_array('createMerek', $permsArr, true);
        $canEdit   = in_array('updateMerek', $permsArr, true);
        $canDelete = in_array('deleteMerek', $permsArr, true);

        return view('merek.index', compact('mereks', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('merek.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fmerekcode' => strtoupper($request->fmerekcode),
            'fmerekname' => strtoupper($request->fmerekname),
        ]);

        $validated = $request->validate(
            [
                'fmerekcode' => 'required|string|unique:msmerek,fmerekcode',
                'fmerekname' => 'required|string|unique:msmerek,fmerekname',
            ],
            [
                'fmerekcode.required' => 'Kode Merek harus diisi.',
                'fmerekname.required' => 'Nama Merek harus diisi.',
                'fmerekcode.unique' => 'Kode Merek sudah ada, silakan gunakan kode lain.',
                'fmerekname.unique' => 'Nama Merek sudah ada, silakan gunakan nama lain.',
            ]
        );

        $validated['fmerekcode'] = strtoupper($validated['fmerekcode']);
        $validated['fmerekname'] = strtoupper($validated['fmerekname']);

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Merek
        Merek::create($validated);

        return redirect()
            ->route('merek.create')
            ->with('success', 'Merek berhasil ditambahkan.');
    }

    public function edit($fmerekid)
    {
        // Ambil data berdasarkan PK fmerekid
        $merek = Merek::findOrFail($fmerekid);

        return view('merek.edit', compact('merek'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fmerekid)
    {
        $request->merge([
            'fmerekcode' => strtoupper($request->fmerekcode),
            'fmerekname' => strtoupper($request->fmerekname),
        ]);

        $validated = $request->validate(
            [
                'fmerekcode' => "required|string|unique:msmerek,fmerekcode,{$fmerekid},fmerekid",
                'fmerekname' => 'required|string|unique:msmerek,fmerekname',
            ],
            [
                'fmerekcode.required' => 'Kode Merek harus diisi.',
                'fmerekname.required' => 'Nama Merek harus diisi.',
                'fmerekcode.unique' => 'Kode Merek sudah ada, silakan gunakan kode lain.',
                'fmerekname.unique' => 'Nama Merek sudah ada, silakan gunakan nama lain.',
            ]
        );

        $validated['fmerekcode'] = strtoupper($validated['fmerekcode']);
        $validated['fmerekname'] = strtoupper($validated['fmerekname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        $merek = Merek::findOrFail($fmerekid);
        $merek->update($validated);

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil di-update.');
    }

    public function destroy($fmerekid)
    {
        $merek = Merek::findOrFail($fmerekid);
        $merek->delete();

        return redirect()
            ->route('merek.index')
            ->with('success', 'Merek berhasil dihapus.');
    }

    public function browse(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $query = Merek::query()
            ->select('fmerekid', 'fmerekcode', 'fmerekname', 'fnonactive')
            // hanya aktif (sesuaikan logika aktif/nonaktif Anda)
            ->where(function ($w) {
                $w->whereNull('fnonactive')->orWhere('fnonactive', '!=', '1')->orWhere('fnonactive', '!=', 'Y');
            });

        if ($q !== '') {
            // jika pakai Postgres: 'ilike', jika MySQL pakai 'like'
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('fmerekcode', 'like', $like)
                    ->orWhere('fmerekname', 'like', $like);
            });
        }

        $paginated = $query->orderBy('fmerekcode')->paginate($perPage);

        return response()->json($paginated);
    }
}
