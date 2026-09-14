<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealerController extends Controller
{
    private function ensureDealerPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu dealer.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureDealerPermission('viewDealer')) {
            return $guard;
        }

        $dealers = Dealer::orderBy('fdealercode', 'asc')
            ->get(['fdealerid', 'fdealercode', 'fdealername', 'fnonactive']);

        $permsStr = (string) session('user_restricted_permissions', '');
        $permsArr = explode(',', $permsStr);
        $canCreate = in_array('createDealer', $permsArr, true);
        $canEdit = in_array('updateDealer', $permsArr, true);
        $canDelete = in_array('deleteDealer', $permsArr, true);

        return view('dealer.index', compact('dealers', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureDealerPermission('createDealer')) {
            return $guard;
        }

        return view('dealer.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureDealerPermission('createDealer')) {
            return $guard;
        }

        $request->merge([
            'fdealercode' => strtoupper($request->fdealercode),
        ]);

        $validated = $request->validate(
            [
                'fdealercode' => 'required|string|unique:msdealer,fdealercode',
                'fdealername' => 'required|string',
            ],
            [
                'fdealercode.unique' => 'Kode dealer sudah ada.',
                'fdealercode.required' => 'Kode dealer wajib diisi.',
                'fdealername.required' => 'Nama dealer wajib diisi.',
            ]
        );

        $validated['fdealercode'] = strtoupper($validated['fdealercode']);
        $validated['fdealername'] = strtoupper($validated['fdealername']);
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();
        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';

        $dealer = Dealer::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'id' => $dealer->fdealerid,
                'code' => $dealer->fdealercode,
                'name' => $dealer->fdealername,
            ]);
        }

        return redirect()
            ->route('dealer.create')
            ->with('success', 'Dealer berhasil disimpan.');
    }

    public function edit($fdealerid)
    {
        if ($guard = $this->ensureDealerPermission('updateDealer')) {
            return $guard;
        }

        $dealer = Dealer::findOrFail($fdealerid);

        return view('dealer.edit', [
            'dealer' => $dealer,
            'action' => 'edit',
        ]);
    }

    public function view($fdealerid)
    {
        if ($guard = $this->ensureDealerPermission('viewDealer')) {
            return $guard;
        }

        $dealer = Dealer::findOrFail($fdealerid);

        return view('dealer.view', [
            'dealer' => $dealer,
        ]);
    }

    public function update(Request $request, $fdealerid)
    {
        if ($guard = $this->ensureDealerPermission('updateDealer')) {
            return $guard;
        }

        try {
            $request->merge([
                'fdealercode' => strtoupper($request->fdealercode),
            ]);

            $validated = $request->validate(
                [
                    'fdealercode' => "required|string|unique:msdealer,fdealercode,{$fdealerid},fdealerid",
                    'fdealername' => 'required|string',
                ],
                [
                    'fdealercode.unique' => 'Kode dealer sudah ada.',
                    'fdealercode.required' => 'Kode dealer wajib diisi.',
                    'fdealername.required' => 'Nama dealer wajib diisi.',
                ]
            );

            $validated['fdealercode'] = strtoupper($validated['fdealercode']);
            $validated['fdealername'] = strtoupper($validated['fdealername']);

            $userLogin = auth('sysuser')->user();
            $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';
            $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
            $validated['fupdatedat'] = now();

            $dealer = Dealer::findOrFail($fdealerid);

            DB::table('logdealer')->insert([
                'fdealerid'    => $dealer->fdealerid,
                'fdealercode'  => $dealer->fdealercode,
                'fdealername'  => $dealer->fdealername,
                'fcreatedat'   => $dealer->fcreatedat,
                'fupdatedat'   => $dealer->fupdatedat,
                'fcreatedby'   => $dealer->fcreatedby,
                'fupdatedby'   => $dealer->fupdatedby,
                'fnonactive'   => $dealer->fnonactive,
                'feditmode'    => 'U',
                'fuseridlog'   => $userLogin->fname ?? ($userLogin->fuserid ?? null),
                'fdatetimelog' => now(),
            ]);

            $dealer->update($validated);

            return redirect()
                ->route('dealer.index')
                ->with('success', 'Dealer berhasil diupdate.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Gagal mengupdate dealer. Cek data.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate dealer: ' . $e->getMessage());
        }
    }

    public function delete($fdealerid)
    {
        if ($guard = $this->ensureDealerPermission('deleteDealer')) {
            return $guard;
        }

        $dealer = Dealer::findOrFail($fdealerid);

        return view('dealer.delete', [
            'dealer' => $dealer,
        ]);
    }

    public function destroy($fdealerid)
    {
        if (! $this->hasRestrictedPermission('deleteDealer')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu dealer.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu dealer.');
        }

        try {
            $dealer = Dealer::findOrFail($fdealerid);
            $userLogin = auth('sysuser')->user();

            DB::table('logdealer')->insert([
                'fdealerid'    => $dealer->fdealerid,
                'fdealercode'  => $dealer->fdealercode,
                'fdealername'  => $dealer->fdealername,
                'fcreatedat'   => $dealer->fcreatedat,
                'fupdatedat'   => $dealer->fupdatedat,
                'fcreatedby'   => $dealer->fcreatedby,
                'fupdatedby'   => $dealer->fupdatedby,
                'fnonactive'   => $dealer->fnonactive,
                'feditmode'    => 'D',
                'fuseridlog'   => $userLogin->fname ?? ($userLogin->fuserid ?? null),
                'fdatetimelog' => now(),
            ]);

            $dealer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dealer ' . $dealer->fdealername . ' berhasil dihapus.',
                'redirect' => route('dealer.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dealer belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        if ($guard = $this->ensureDealerPermission('viewDealer')) {
            return $guard;
        }

        $query = Dealer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fdealercode', 'ilike', "%{$search}%")
                    ->orWhere('fdealername', 'ilike', "%{$search}%");
            });
        }

        $recordsTotal = Dealer::count();
        $recordsFiltered = $query->count();

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $data = $query->orderBy('fdealercode', 'asc')
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
}
