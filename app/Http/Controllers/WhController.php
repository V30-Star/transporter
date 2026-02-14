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
        // Tambahkan fbranchname ke allowedSorts jika ingin bisa diurutkan berdasarkan nama cabang
        $allowedSorts = ['fwhcode', 'fwhname', 'fwhid', 'faddress', 'fnonactive', 'fbranchcode', 'fbranchname'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fwhid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        // Memulai query dengan join ke mscabang
        $query = Wh::query()
            ->leftJoin('mscabang', 'mswh.fbranchcode', '=', 'mscabang.fcabangkode');
        // Catatan: Pastikan nama tabel & primary key sesuai (biasanya mscabang.fbranchcode atau fcabangkode)

        if ($status === 'active') {
            $query->where('mswh.fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('mswh.fnonactive', '1');
        }

        $gudangs = $query
            ->orderBy($sortBy, $sortDir)
            ->get([
                'mswh.fwhcode',
                'mswh.fwhname',
                'mswh.fwhid',
                'mswh.faddress',
                'mswh.fnonactive',
                'mswh.fbranchcode',
                'mscabang.fcabangname' // Mengambil kolom nama cabang dari tabel mscabang
            ]);

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

        return view('gudang.edit', [
            'gudang' => $gudang,
            'cabangOptions' => $cabangOptions,
            'action' => 'edit'
        ]);
    }

    public function view($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);

        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.view', [
            'gudang' => $gudang,
            'cabangOptions' => $cabangOptions
        ]);
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

    public function delete($fwhid)
    {
        $gudang = Wh::findOrFail($fwhid);

        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.edit', [
            'gudang' => $gudang,
            'cabangOptions' => $cabangOptions,
            'action' => 'delete'
        ]);
    }

    public function destroy($fwhid)
    {
        try {
            $gudang = Wh::findOrFail($fwhid);
            $gudang->delete();

            return redirect()->route('gudang.index')->with('success', 'Data gudang ' . $gudang->fwhname . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('gudang.delete', $fwhid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function browse(Request $request)
    {
        // 1. Ambil kode cabang dari session user login
        // Pastikan session 'fcabang' sudah diset saat proses login
        $userBranch = session('fcabang');

        $rawPermissions = session('user_restricted_permissions', '');
        $userPermissions = array_map('trim', explode(',', $rawPermissions));

        // 2. Base query
        $query = Wh::query();

        $canAccessAllBranches = in_array('semuacabang', $userPermissions);

        // 3. TAMBAHKAN VALIDASI CABANG DISINI
        // Wh hanya boleh melihat data yang fbranchcode-nya sama dengan session user
        if (!$canAccessAllBranches) {
            if ($userBranch) {
                $query->where('fbranchcode', $userBranch);
            } else {
                // Jika tidak punya akses semua dan tidak ada cabang, kosongkan data
                $query->whereRaw('1 = 0');
            }
        }

        // Total records tanpa filter pencarian (tapi tetap terfilter cabang)
        $recordsTotal = (clone $query)->count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fwhcode', 'ilike', "%{$search}%")
                    ->orWhere('fwhname', 'ilike', "%{$search}%")
                    ->orWhere('fbranchcode', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter pencarian
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fwhcode');
        $orderDir = $request->input('order_dir', 'asc');

        $allowedColumns = ['fwhcode', 'fwhname', 'fbranchcode'];
        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fwhcode', 'asc');
        }

        // Pagination
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)
            ->take($length)
            ->get();

        // Response format untuk DataTables
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data
        ]);
    }
}
