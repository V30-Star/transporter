<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesmanController extends Controller
{
    private function ensureSalesmanPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu salesman.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureSalesmanPermission('viewSalesman')) {
            return $guard;
        }

        $salesmans = Salesman::orderBy('fsalesmancode', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname', 'fsalesmanid', 'fnonactive']);

        $permsStr = (string) session('user_restricted_permissions', '');
        $permsArr = explode(',', $permsStr);
        $canCreate = in_array('createSalesman', $permsArr, true);
        $canEdit = in_array('updateSalesman', $permsArr, true);
        $canDelete = in_array('deleteSalesman', $permsArr, true);

        return view('salesman.index', compact('salesmans', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureSalesmanPermission('createSalesman')) {
            return $guard;
        }

        return view('salesman.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureSalesmanPermission('createSalesman')) {
            return $guard;
        }

        $request->merge([
            'fsalesmancode' => strtoupper($request->fsalesmancode),
        ]);

        $validated = $request->validate(
            [
                'fsalesmancode' => 'required|string|unique:mssalesman,fsalesmancode',
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode salesman wajib diisi.',
                'fsalesmanname.required' => 'Nama salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode salesman sudah ada.',
            ]
        );

        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';

        Salesman::create($validated);

        return redirect()
            ->route('salesman.create')
            ->with('success', 'Salesman berhasil disimpan.');
    }

    public function edit($fsalesmanid)
    {
        if ($guard = $this->ensureSalesmanPermission('updateSalesman')) {
            return $guard;
        }

        $salesman = Salesman::findOrFail($fsalesmanid);
        $isTransactionLocked = $this->hasTransactionUsage($salesman);

        return view('salesman.edit', [
            'salesman' => $salesman,
            'isTransactionLocked' => $isTransactionLocked,
            'action' => 'edit',
        ]);
    }

    public function view($fsalesmanid)
    {
        if ($guard = $this->ensureSalesmanPermission('viewSalesman')) {
            return $guard;
        }

        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.view', [
            'salesman' => $salesman,
        ]);
    }

    public function update(Request $request, $fsalesmanid)
    {
        if ($guard = $this->ensureSalesmanPermission('updateSalesman')) {
            return $guard;
        }

        $salesman = Salesman::findOrFail($fsalesmanid);
        $isTransactionLocked = $this->hasTransactionUsage($salesman);

        $request->merge([
            'fsalesmancode' => strtoupper($isTransactionLocked ? $salesman->fsalesmancode : $request->fsalesmancode),
        ]);

        $validated = $request->validate(
            [
                'fsalesmancode' => "required|string|unique:mssalesman,fsalesmancode,{$fsalesmanid},fsalesmanid",
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode salesman wajib diisi.',
                'fsalesmanname.required' => 'Nama salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode salesman sudah ada.',
            ]
        );

        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);

        $validated['fnonactive'] = $request->boolean('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        if ($isTransactionLocked) {
            $validated['fsalesmancode'] = $salesman->fsalesmancode;
        }

        $salesman->update($validated);

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil diupdate.');
    }

    public function delete($fsalesmanid)
    {
        if ($guard = $this->ensureSalesmanPermission('deleteSalesman')) {
            return $guard;
        }

        $salesman = Salesman::findOrFail($fsalesmanid);

        if ($message = $this->getUsageLockMessage($salesman)) {
            return redirect()->route('salesman.view', $salesman->fsalesmanid)->with('error', $message);
        }

        return view('salesman.delete', [
            'salesman' => $salesman,
        ]);
    }

    public function destroy($fsalesmanid)
    {
        if (! $this->hasRestrictedPermission('deleteSalesman')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu salesman.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu salesman.');
        }

        try {
            $salesman = Salesman::findOrFail($fsalesmanid);

            if ($message = $this->getUsageLockMessage($salesman)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('salesman.view', $salesman->fsalesmanid),
                    ], 422);
                }

                return redirect()->route('salesman.view', $salesman->fsalesmanid)->with('error', $message);
            }

            $salesman->delete();

            return response()->json([
                'success' => true,
                'message' => 'Salesman '.$salesman->fsalesmanname.' berhasil dihapus.',
                'redirect' => route('salesman.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Salesman belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        if ($guard = $this->ensureSalesmanPermission('viewSalesman')) {
            return $guard;
        }

        $query = Salesman::query();

        $recordsTotal = Salesman::count();

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fsalesmancode', 'ilike', "%{$search}%")
                    ->orWhere('fsalesmanname', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fsalesmanname');
        $orderDir = $request->input('order_dir', 'asc');

        $allowedColumns = ['fsalesmancode', 'fsalesmanname'];
        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fsalesmanname', 'asc');
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

    private function hasTransactionUsage(Salesman $salesman): bool
    {
        return DB::table('mscustomer')->where('fsalesman', $salesman->fsalesmanid)->exists();
    }

    private function getUsageLockMessage(Salesman $salesman): ?string
    {
        if (! $this->hasTransactionUsage($salesman)) {
            return null;
        }

        return 'Salesman ' . strtoupper((string) $salesman->fsalesmancode) . ' tidak bisa dihapus. Sudah direferensi di transaksi.';
    }
}
