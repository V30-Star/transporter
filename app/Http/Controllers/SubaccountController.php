<?php

namespace App\Http\Controllers;

use App\Models\Subaccount;
use Illuminate\Http\Request;

class SubaccountController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fsubaccountcode', 'fsubaccountname', 'fsubaccountid', 'fnonactive'];
        $sortBy = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fsubaccountid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Subaccount::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $subaccounts = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fsubaccountcode', 'fsubaccountname', 'fsubaccountid', 'fnonactive']);

        $canCreate = in_array('createSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateSubAccount', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSubAccount', explode(',', session('user_restricted_permissions', '')));

        return view('subaccount.index', compact('subaccounts', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function create()
    {
        return view('subaccount.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fsubaccountcode' => strtoupper($request->fsubaccountcode),
        ]);

        $validated = $request->validate(
            [
                'fsubaccountcode' => 'required|string|unique:mssubaccount,fsubaccountcode',
                'fsubaccountname' => 'required|string',
            ],
            [
                'fsubaccountcode.required' => 'Kode subaccount harus diisi.',
                'fsubaccountname.required' => 'Nama subaccount harus diisi.',
                'fsubaccountcode.unique' => 'Kode subaccount sudah ada.',
            ]
        );

        $validated['fsubaccountcode'] = strtoupper($validated['fsubaccountcode']);
        $validated['fsubaccountname'] = strtoupper($validated['fsubaccountname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Subaccount::create($validated);

        return redirect()
            ->route('subaccount.create')
            ->with('success', 'Subaccount berhasil ditambahkan.');
    }

    public function edit($fsubaccountid)
    {
        $subaccount = Subaccount::findOrFail($fsubaccountid);

        return view('subaccount.edit', [
            'subaccount' => $subaccount,
            'action' => 'edit',
        ]);
    }

    public function view($fsubaccountid)
    {
        $subaccount = Subaccount::findOrFail($fsubaccountid);

        return view('subaccount.view', [
            'subaccount' => $subaccount,
        ]);
    }

    public function update(Request $request, $fsubaccountid)
    {
        $validated = $request->validate(
            [
                'fsubaccountcode' => "required|string|unique:mssubaccount,fsubaccountcode,{$fsubaccountid},fsubaccountid",
                'fsubaccountname' => 'required|string',
            ],
            [
                'fsubaccountcode.required' => 'Kode subaccount harus diisi.',
                'fsubaccountname.required' => 'Nama subaccount harus diisi.',
                'fsubaccountcode.unique' => 'Kode subaccount sudah ada.',
            ]
        );

        $validated['fsubaccountcode'] = strtoupper($validated['fsubaccountcode']);
        $validated['fsubaccountname'] = strtoupper($validated['fsubaccountname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $subaccount = Subaccount::findOrFail($fsubaccountid);
        $subaccount->update($validated);

        return redirect()
            ->route('subaccount.index')
            ->with('success', 'Subaccount berhasil di-update.');
    }

    public function delete($fsubaccountid)
    {
        $subaccount = Subaccount::findOrFail($fsubaccountid);

        return view('subaccount.delete', [
            'subaccount' => $subaccount,
        ]);
    }

    public function destroy($fsubaccountid)
    {
        try {
            $subaccount = Subaccount::findOrFail($fsubaccountid);

            if (\Illuminate\Support\Facades\DB::table('jurnaldt')->where('fsubaccount', $subaccount->fsubaccount)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subaccount sudah digunakan dalam transaksi jurnal.',
                ], 422);
            }

            $subaccount->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data subaccount '.$subaccount->fsubaccountname.' berhasil dihapus.',
                'redirect' => route('subaccount.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function browse(Request $request)
    {
        $query = Subaccount::query()
            ->where('fnonactive', '0');

        $recordsTotal = Subaccount::query()
            ->where('fnonactive', '0')
            ->count();

        if ($request->filled('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fsubaccountcode', 'ilike', "%{$search}%")
                    ->orWhere('fsubaccountname', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fsubaccountname');
        $orderDir = $request->input('order_dir', 'asc');
        $allowedColumns = ['fsubaccountcode', 'fsubaccountname'];

        if (in_array($orderColumn, $allowedColumns, true)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fsubaccountname', 'asc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)
            ->take($length)
            ->get(['fsubaccountid', 'fsubaccountcode', 'fsubaccountname']);

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }
}
