<?php

namespace App\Http\Controllers;

use App\Models\Groupproduct;
use Illuminate\Http\Request;

class GroupproductController extends Controller
{
    private function ensureGroupProductPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu group product.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureGroupProductPermission('viewGroupProduct')) {
            return $guard;
        }

        $groupproducts = Groupproduct::orderBy('fgroupcode', 'asc')
            ->get(['fgroupcode', 'fgroupname', 'fgroupid', 'fnonactive']);

        $canCreate = in_array('createGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateGroupProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupProduct', explode(',', session('user_restricted_permissions', '')));

        return view('groupproduct.index', compact('groupproducts', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureGroupProductPermission('createGroupProduct')) {
            return $guard;
        }

        return view('groupproduct.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureGroupProductPermission('createGroupProduct')) {
            return $guard;
        }

        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);

        $validated = $request->validate(
            [
                'fgroupcode' => 'required|string|unique:ms_groupprd,fgroupcode',
                'fgroupname' => 'required|string',
            ],
            [
                'fgroupcode.unique' => 'Kode group produk sudah ada.',
                'fgroupcode.required' => 'Kode group produk wajib diisi.',
                'fgroupname.required' => 'Nama group produk wajib diisi.',
            ]
        );

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';

        $group = Groupproduct::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'id' => $group->fgroupid,
                'name' => $group->fgroupname,
                'code' => $group->fgroupcode,
            ]);
        }

        return redirect()
            ->route('groupproduct.create')
            ->with('success', 'Group product berhasil disimpan.');
    }

    public function edit($fgroupid)
    {
        if ($guard = $this->ensureGroupProductPermission('updateGroupProduct')) {
            return $guard;
        }

        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $isUsedInTransactions = $this->isGroupProductUsedInTransactions($groupproduct->fgroupcode);

        return view('groupproduct.edit', [
            'groupproduct' => $groupproduct,
            'action' => 'edit',
            'isUsedInTransactions' => $isUsedInTransactions,
        ]);
    }

    public function view($fgroupid)
    {
        if ($guard = $this->ensureGroupProductPermission('viewGroupProduct')) {
            return $guard;
        }

        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $isUsedInTransactions = $this->isGroupProductUsedInTransactions($groupproduct->fgroupcode);

        return view('groupproduct.view', [
            'groupproduct' => $groupproduct,
            'isUsedInTransactions' => $isUsedInTransactions,
        ]);
    }

    public function update(Request $request, $fgroupid)
    {
        if ($guard = $this->ensureGroupProductPermission('updateGroupProduct')) {
            return $guard;
        }

        $groupproduct = Groupproduct::findOrFail($fgroupid);
        $oldGroupCode = $groupproduct->fgroupcode;

        $request->merge([
            'fgroupcode' => strtoupper($request->fgroupcode),
        ]);

        $newGroupCode = $request->fgroupcode;
        $isUsed = $this->isGroupProductUsedInTransactions($oldGroupCode);

        if ($isUsed && trim($oldGroupCode) !== trim($newGroupCode)) {
            return redirect()->back()
                ->withErrors(['fgroupcode' => 'Kode group produk tidak bisa diubah karena produk dalam group ini sudah digunakan dalam transaksi.'])
                ->withInput();
        }

        $validated = $request->validate(
            [
                'fgroupcode' => "required|string|unique:ms_groupprd,fgroupcode,{$fgroupid},fgroupid",
                'fgroupname' => 'required|string',
            ],
            [
                'fgroupcode.unique' => 'Kode group produk sudah ada.',
                'fgroupcode.required' => 'Kode group produk wajib diisi.',
                'fgroupname.required' => 'Nama group produk wajib diisi.',
            ]
        );

        $validated['fgroupcode'] = strtoupper($validated['fgroupcode']);
        $validated['fgroupname'] = strtoupper($validated['fgroupname']);

        $userLogin = auth('sysuser')->user();
        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        if (trim($oldGroupCode) !== trim($newGroupCode) && !$isUsed) {
            \Illuminate\Support\Facades\DB::table('msprd')
                ->whereRaw('TRIM(fgroupcode) = ?', [trim($oldGroupCode)])
                ->update(['fgroupcode' => $newGroupCode]);
        }

        // 2. Selalu INSERT log baru (feditmode = 'U')
        \Illuminate\Support\Facades\DB::table('loggroupcustomer')->insert([
            'fgroupid'     => $groupproduct->fgroupid,
            'fgroupcode'   => $groupproduct->fgroupcode,
            'fgroupname'   => $groupproduct->fgroupname,
            'fcreatedat'   => $groupproduct->fcreatedat,
            'fupdatedat'   => $groupproduct->fupdatedat,
            'fcreatedby'   => $groupproduct->fcreatedby,
            'fupdatedby'   => $groupproduct->fupdatedby,
            'fnonactive'   => $groupproduct->fnonactive,
            'feditmode'    => 'U', // Update
            'fuseridlog'   => $userLogin->fname ?? null,
            'fdatetimelog' => now(),
        ]);

        $groupproduct->update($validated);

        return redirect()
            ->route('groupproduct.index')
            ->with('success', 'Group product berhasil diupdate.');
    }

    public function delete($fgroupid)
    {
        if ($guard = $this->ensureGroupProductPermission('deleteGroupProduct')) {
            return $guard;
        }

        $groupproduct = Groupproduct::findOrFail($fgroupid);

        return view('groupproduct.delete', [
            'groupproduct' => $groupproduct,
        ]);
    }

    public function destroy($fgroupid)
    {
        if (! $this->hasRestrictedPermission('deleteGroupProduct')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu group product.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu group product.');
        }

        try {
            $groupproduct = Groupproduct::findOrFail($fgroupid);

            if (\Illuminate\Support\Facades\DB::table('msprd')->where('fgroupcode', $groupproduct->fgroupcode)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group product tidak bisa dihapus. Sudah direferensi di produk.',
                ], 422);
            }

            $userLogin = auth('sysuser')->user();

            // 1. Selalu INSERT log baru sebelum data utama di-delete (feditmode = 'D')
            \Illuminate\Support\Facades\DB::table('loggroupcustomer')->insert([
                'fgroupid'     => $groupproduct->fgroupid,
                'fgroupcode'   => $groupproduct->fgroupcode,
                'fgroupname'   => $groupproduct->fgroupname,
                'fcreatedat'   => $groupproduct->fcreatedat,
                'fupdatedat'   => $groupproduct->fupdatedat,
                'fcreatedby'   => $groupproduct->fcreatedby,
                'fupdatedby'   => $groupproduct->fupdatedby,
                'fnonactive'   => $groupproduct->fnonactive,
                'feditmode'    => 'D', // Delete
                'fuseridlog'   => $userLogin->fname ?? null,
                'fdatetimelog' => now(),
            ]);

            $groupproduct->delete();

            return response()->json([
                'success' => true,
                'message' => 'Group product ' . $groupproduct->fgroupname . ' berhasil dihapus.',
                'redirect' => route('groupproduct.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Group product belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        if ($guard = $this->ensureGroupProductPermission('viewGroupProduct')) {
            return $guard;
        }

        $query = Groupproduct::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fgroupcode', 'ilike', "%{$search}%")
                    ->orWhere('fgroupname', 'ilike', "%{$search}%");
            });
        }

        $recordsTotal = Groupproduct::count();
        $recordsFiltered = $query->count();

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $data = $query->orderBy('fgroupcode', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    protected function isGroupProductUsedInTransactions($groupCode)
    {
        if (empty($groupCode)) {
            return false;
        }

        $trimmedCode = trim($groupCode);

        $products = \Illuminate\Support\Facades\DB::table('msprd')
            ->whereRaw('TRIM(fgroupcode) = ?', [$trimmedCode])
            ->get(['fprdid', 'fprdcode']);

        if ($products->isEmpty()) {
            return false;
        }

        $productIds = $products->pluck('fprdid')->filter()->all();
        $productCodes = $products->pluck('fprdcode')->filter()->all();

        if (empty($productIds) && empty($productCodes)) {
            return false;
        }

        if (!empty($productCodes) && \Illuminate\Support\Facades\DB::table('tr_prd')->whereIn('fprdcode', $productCodes)->exists()) {
            return true;
        }

        $poQuery = \Illuminate\Support\Facades\DB::table('tr_pod');
        $hasPo = false;
        if (!empty($productIds)) {
            $poQuery->where(function ($q) use ($productIds, $productCodes) {
                $q->whereIn('fprdid', $productIds);
                if (!empty($productCodes)) {
                    $q->orWhereIn('fprdcode', $productCodes);
                }
            });
            $hasPo = $poQuery->exists();
        } elseif (!empty($productCodes)) {
            $hasPo = $poQuery->whereIn('fprdcode', $productCodes)->exists();
        }
        if ($hasPo) {
            return true;
        }

        if (!empty($productCodes) && \Illuminate\Support\Facades\DB::table('trstockdt')->whereIn('fprdcode', $productCodes)->exists()) {
            return true;
        }

        if (!empty($productCodes) && \Illuminate\Support\Facades\DB::table('trsodt')->whereIn('fprdcode', $productCodes)->exists()) {
            return true;
        }

        if (!empty($productCodes) && \Illuminate\Support\Facades\DB::table('trandt')->whereIn('fprdcode', $productCodes)->exists()) {
            return true;
        }

        return false;
    }
}
