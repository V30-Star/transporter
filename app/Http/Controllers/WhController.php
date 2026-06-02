<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Wh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhController extends Controller
{
    private function ensureGudangPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu gudang.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureGudangPermission('viewGudang')) {
            return $guard;
        }

        $gudangs = Wh::query()
            ->leftJoin('mscabang', 'mswh.fbranchcode', '=', 'mscabang.fcabangkode')
            ->orderBy('fwhcode', 'asc')
            ->get([
                'mswh.fwhcode',
                'mswh.fwhname',
                'mswh.fwhid',
                'mswh.faddress',
                'mswh.fnonactive',
                'mswh.fbranchcode',
                'mscabang.fcabangname',
            ]);

        $permsArr = explode(',', (string) session('user_restricted_permissions', ''));
        $canCreate = in_array('createGudang', $permsArr, true);
        $canEdit = in_array('updateGudang', $permsArr, true);
        $canDelete = in_array('deleteGudang', $permsArr, true);

        return view('gudang.index', compact('gudangs', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureGudangPermission('createGudang')) {
            return $guard;
        }

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
        if ($guard = $this->ensureGudangPermission('createGudang')) {
            return $guard;
        }

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
                'fwhcode.unique' => 'Kode gudang sudah ada.',
                'fwhcode.required' => 'Kode gudang wajib diisi.',
                'fwhname.required' => 'Nama gudang wajib diisi.',
                'faddress.required' => 'Alamat gudang wajib diisi.',
                'fbranchcode.required' => 'Kode cabang wajib dipilih.',
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
            ->with('success', 'Gudang berhasil disimpan.');
    }

    public function edit($fwhid)
    {
        if ($guard = $this->ensureGudangPermission('updateGudang')) {
            return $guard;
        }

        $gudang = Wh::findOrFail($fwhid);
        $isTransactionLocked = $this->hasTransactionUsage($gudang);

        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.edit', [
            'gudang' => $gudang,
            'cabangOptions' => $cabangOptions,
            'isTransactionLocked' => $isTransactionLocked,
            'action' => 'edit',
        ]);
    }

    public function view($fwhid)
    {
        if ($guard = $this->ensureGudangPermission('viewGudang')) {
            return $guard;
        }

        $gudang = Wh::findOrFail($fwhid);

        $cabangOptions = Cabang::query()
            ->selectRaw('TRIM(BOTH FROM fcabangkode) AS fbranchcode, fcabangname')
            ->where('fnonactive', '0')
            ->whereNotNull('fcabangkode')
            ->orderBy('fcabangname')
            ->get();

        return view('gudang.view', [
            'gudang' => $gudang,
            'cabangOptions' => $cabangOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $fwhid)
    {
        if ($guard = $this->ensureGudangPermission('updateGudang')) {
            return $guard;
        }

        $gudang = Wh::findOrFail($fwhid);
        $isTransactionLocked = $this->hasTransactionUsage($gudang);

        $request->merge([
            'fwhcode' => strtoupper($isTransactionLocked ? $gudang->fwhcode : $request->fwhcode),
        ]);

        $validated = $request->validate(
            [
                'fwhcode' => "required|string|unique:mswh,fwhcode,{$fwhid},fwhid",
                'fwhname' => 'required|string',
                'faddress' => 'required|string',
                'fbranchcode' => 'required|string', // Ensure the cabang code is validated and passed
            ],
            [
                'fwhcode.unique' => 'Kode gudang sudah ada.',
                'fwhcode.required' => 'Kode gudang wajib diisi.',
                'fwhname.required' => 'Nama gudang wajib diisi.',
                'faddress.required' => 'Alamat gudang wajib diisi.',
                'fbranchcode.required' => 'Kode cabang wajib dipilih.',
            ]
        );

        // Add default values for the required fields
        $validated['fwhcode'] = strtoupper($validated['fwhcode']);
        $validated['fwhname'] = strtoupper($validated['fwhname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's name or 'system' as default
        $validated['fupdatedat'] = now(); // Use the current time

        // Cari dan update
        if ($isTransactionLocked) {
            $validated['fwhcode'] = $gudang->fwhcode;
        }

        $gudang->update($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil diupdate.');
    }

    public function delete($fwhid)
    {
        if ($guard = $this->ensureGudangPermission('deleteGudang')) {
            return $guard;
        }

        $gudang = Wh::findOrFail($fwhid);

        if ($message = $this->getUsageLockMessage($gudang)) {
            return redirect()->route('gudang.view', $gudang->fwhid)->with('error', $message);
        }

        return view('gudang.delete', [
            'gudang' => $gudang,
        ]);
    }

    public function destroy($fwhid)
    {
        if (! $this->hasRestrictedPermission('deleteGudang')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu gudang.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu gudang.');
        }

        try {
            $gudang = Wh::findOrFail($fwhid);

            if ($message = $this->getUsageLockMessage($gudang)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('gudang.view', $gudang->fwhid),
                    ], 422);
                }

                return redirect()->route('gudang.view', $gudang->fwhid)->with('error', $message);
            }

            $gudang->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gudang '.$gudang->fwhname.' berhasil dihapus.',
                'redirect' => route('gudang.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gudang belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        if ($guard = $this->ensureGudangPermission('viewGudang')) {
            return $guard;
        }

        // 1. Ambil kode cabang dari user login (sysuser table, column fbranch)
        $sysuser = auth('sysuser')->user();
        $userBranch = $sysuser ? ($sysuser->fbranch ?? $sysuser->fcabang) : session('fcabang');

        $rawPermissions = session('user_restricted_permissions', '');
        $userPermissions = array_map('trim', explode(',', $rawPermissions));

        // 2. Base query
        $query = Wh::query();

        $canAccessAllBranches = in_array('semuacabang', $userPermissions);

        // 3. TAMBAHKAN VALIDASI CABANG DISINI
        // Wh hanya boleh melihat data yang fbranchcode-nya sama dengan session user
        if (! $canAccessAllBranches) {
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
            'data' => $data,
        ]);
    }

    private function hasTransactionUsage(Wh $gudang): bool
    {
        $warehouseCode = trim((string) $gudang->fwhcode);

        if ($warehouseCode === '') {
            return false;
        }

        return DB::table('trstockmt')->where('ffrom', $warehouseCode)->exists()
            || DB::table('trstockmt')->where('fto', $warehouseCode)->exists();
    }

    private function getUsageLockMessage(Wh $gudang): ?string
    {
        if (! $this->hasTransactionUsage($gudang)) {
            return null;
        }

        return 'Gudang ' . strtoupper((string) $gudang->fwhcode) . ' tidak bisa dihapus. Sudah direferensi di transaksi.';
    }
}
