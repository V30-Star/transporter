<?php

namespace App\Http\Controllers;

use App\Models\Salesman;
use Illuminate\Http\Request;

class SalesmanController extends Controller
{
    public function index(Request $request)
    {
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
        return view('salesman.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'fsalesmancode' => strtoupper($request->fsalesmancode),
        ]);

        $validated = $request->validate(
            [
                'fsalesmancode' => 'required|string|unique:mssalesman,fsalesmancode',
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode Salesman wajib diisi.',
                'fsalesmanname.required' => 'Nama Salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode Salesman sudah ada.',
            ]
        );

        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Salesman::create($validated);

        return redirect()
            ->route('salesman.create')
            ->with('success', 'Salesman berhasil ditambahkan.');
    }

    public function edit($fsalesmanid)
    {
        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.edit', [
            'salesman' => $salesman,
            'action' => 'edit',
        ]);
    }

    public function view($fsalesmanid)
    {
        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.view', [
            'salesman' => $salesman,
        ]);
    }

    public function update(Request $request, $fsalesmanid)
    {
        $request->merge([
            'fsalesmancode' => strtoupper($request->fsalesmancode),
        ]);

        $validated = $request->validate(
            [
                'fsalesmancode' => "required|string|unique:mssalesman,fsalesmancode,{$fsalesmanid},fsalesmanid",
                'fsalesmanname' => 'required|string',
            ],
            [
                'fsalesmancode.required' => 'Kode Salesman wajib diisi.',
                'fsalesmanname.required' => 'Nama Salesman wajib diisi.',
                'fsalesmancode.unique' => 'Kode Salesman sudah ada.',
            ]
        );

        $validated['fsalesmancode'] = strtoupper($validated['fsalesmancode']);
        $validated['fsalesmanname'] = strtoupper($validated['fsalesmanname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $salesman = Salesman::findOrFail($fsalesmanid);
        $salesman->update($validated);

        return redirect()
            ->route('salesman.index')
            ->with('success', 'Salesman berhasil di-update.');
    }

    public function delete($fsalesmanid)
    {
        $salesman = Salesman::findOrFail($fsalesmanid);

        return view('salesman.delete', [
            'salesman' => $salesman,
        ]);
    }

    public function destroy($fsalesmanid)
    {
        try {
            $salesman = Salesman::findOrFail($fsalesmanid);

            if (\Illuminate\Support\Facades\DB::table('mscustomer')->where('fsalesman', $salesman->fsalesmanid)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salesman sudah digunakan dalam data Customer.',
                ], 422);
            }

            $salesman->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data salesman '.$salesman->fsalesmanname.' berhasil dihapus.',
                'redirect' => route('salesman.index'),
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
}
