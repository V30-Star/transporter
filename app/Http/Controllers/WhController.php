<?php

namespace App\Http\Controllers;

use App\Models\Wh;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fwhcode', 'fwhname', 'fwhid', 'faddress', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fwhid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Wh::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $gudangs = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fwhcode', 'fwhname', 'fwhid', 'faddress', 'fnonactive']);

        $permsArr  = explode(',', (string) session('user_restricted_permissions', ''));
        $canCreate = in_array('createGudang', $permsArr, true);
        $canEdit   = in_array('updateGudang', $permsArr, true);
        $canDelete = in_array('deleteGudang', $permsArr, true);

        return view('gudang.index', compact('gudangs', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.create', compact('cabangOptions'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'fwhcode' => strtoupper($request->fwhcode),
        ]);

        $validated = $request->validate(
            [
                'fwhcode' => 'required|string|unique:mswh,fwhcode',
                'fwhname' => 'required|string',
                'faddress' => 'required|string',
                'fbranchcode' => 'required|string',
            ],
            [
                'fwhcode.unique' => 'Kode Gudang sudah ada.',
                'fwhcode.required' => 'Kode Gudang harus diisi.',
                'fwhname.required' => 'Nama Gudang harus diisi.',
                'faddress.required' => 'Alamat Gudang harus diisi.',
                'fbranchcode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        // Add default values for the required fields
        $validated['fwhcode'] = strtoupper($validated['fwhcode']);
        $validated['fwhname'] = strtoupper($validated['fwhname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Wh::create($validated);

        return redirect()
            ->route('gudang.create')
            ->with('success', 'Wh berhasil ditambahkan.');
    }

    public function edit($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);

        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.edit', compact('gudang', 'cabangOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fwhid)
    {
        $request->merge([
            'fwhcode' => strtoupper($request->fwhcode),
        ]);

        $validated = $request->validate(
            [
                'fwhcode' => "required|string|unique:mswh,fwhcode,{$fwhid},fwhid",
                'fwhname' => 'required|string',
                'faddress' => 'required|string',
                'fbranchcode' => 'required|string', // Ensure the cabang code is validated and passed
            ],
            [
                'fwhcode.unique' => 'Kode Wh sudah ada.',
                'fwhcode.required' => 'Kode Wh harus diisi.',
                'fwhname.required' => 'Nama Wh harus diisi.',
                'faddress.required' => 'Alamat Wh harus diisi.',
                'fbranchcode.required' => 'Kode Cabang harus dipilih.',
            ]
        );

        // Add default values for the required fields
        $validated['fwhcode'] = strtoupper($validated['fwhcode']);
        $validated['fwhname'] = strtoupper($validated['fwhname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        $gudang = Wh::findOrFail($fwhid);
        $gudang->update($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Wh berhasil di-update.');
    }

    public function destroy($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);
        $gudang->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gudang berhasil dihapus.'
            ]);
        }

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Wh berhasil dihapus.');
    }
    public function browse(Request $request)
    {
        $query = Wh::query(); // atau Warehouse::query() sesuai model Anda

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fwhcode', 'ilike', "%{$search}%")
                    ->orWhere('fwhname', 'ilike', "%{$search}%")
                    ->orWhere('fbranchcode', 'ilike', "%{$search}%");
            });
        }

        // Get totals
        $recordsTotal = Wh::count();
        $recordsFiltered = $query->count();

        // Pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $data = $query->orderBy('fwhcode', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }
}
