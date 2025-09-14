<?php

namespace App\Http\Controllers;

use App\Models\Groupcustomer;
use Illuminate\Http\Request;

class GroupcustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->search);

        // Sorting
        $allowedSorts = ['fgroupcode', 'fgroupname', 'fgroupid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fgroupid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $groupCustomers = Groupcustomer::when($search !== '', function ($q) use ($search) {
            $q->where('fgroupcode', 'ILIKE', "%{$search}%")
                ->orWhere('fgroupname', 'ILIKE', "%{$search}%");
        })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('fgroupid', 'desc') // tie-breaker stabil
            ->paginate(10)
            ->withQueryString();

        $canCreate = in_array('createGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupCustomer', explode(',', session('user_restricted_permissions', '')));

        if ($request->ajax()) {
            $rows = collect($groupCustomers->items())->map(function ($gc) {
                return [
                    'fgroupid'    => $gc->fgroupid,
                    'fgroupcode'  => $gc->fgroupcode,
                    'fgroupname'  => $gc->fgroupname,
                    'edit_url'    => route('groupcustomer.edit', $gc->fgroupid),
                    'destroy_url' => route('groupcustomer.destroy', $gc->fgroupid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $groupCustomers->previousPageUrl(),
                    'next'         => $groupCustomers->nextPageUrl(),
                    'current_page' => $groupCustomers->currentPage(),
                    'last_page'    => $groupCustomers->lastPage(),
                ],
                'sort' => ['by' => $sortBy, 'dir' => $sortDir],
            ]);
        }

        return view('master.groupcustomer.index', compact(
            'groupCustomers',
            'search',
            'canCreate',
            'canEdit',
            'canDelete',
            'sortBy',
            'sortDir'
        ));
    }

    public function create()
    {
        // Menampilkan form untuk menambah grup customer baru
        return view('master.groupcustomer.create');
    }

    public function store(Request $request)
    {
        // Validasi input yang diterima dari form
        $validated = $request->validate([
            'fgroupcode' => 'required|string|unique:msgroupcustomer,fgroupcode',
            'fgroupname' => 'required|string',
        ], [
            'fgroupcode.required' => 'Kode Group harus diisi.',
            'fgroupname.required' => 'Nama Group harus diisi.',
            'fgroupcode.unique' => 'Kode Group sudah digunakan, silakan pilih kode lain.',
        ]);

        // Menambahkan nilai default untuk kolom yang tidak ada dalam form
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null; // bisa diganti dengan user yang sedang login
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Menyimpan data grup customer
        Groupcustomer::create($validated);

        // Mengarahkan kembali dengan pesan sukses
        return redirect()->route('groupcustomer.index')
            ->with('success', 'Group Customer berhasil ditambahkan.');
    }

    public function edit($fgroupid)
    {
        // Mengambil data grup customer berdasarkan ID
        $groupCustomer = Groupcustomer::findOrFail($fgroupid);

        // Menampilkan form untuk mengedit grup customer
        return view('master.groupcustomer.edit', compact('groupCustomer'));
    }

    public function update(Request $request, $fgroupid)
    {
        $validated = $request->validate([
            'fgroupcode' => "required|string|unique:msgroupcustomer,fgroupcode,{$fgroupid},fgroupid",
            'fgroupname' => 'required|string',
        ], [
            'fgroupcode.required' => 'Kode Group harus diisi.',
            'fgroupname.required' => 'Nama Group harus diisi.',
            'fgroupcode.unique' => 'Kode Group sudah digunakan, silakan pilih kode lain.',
        ]);

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Mengambil data grup customer berdasarkan ID dan mengupdate
        $groupCustomer = Groupcustomer::findOrFail($fgroupid);
        $groupCustomer->update($validated);

        // Mengarahkan kembali dengan pesan sukses
        return redirect()->route('groupcustomer.index')
            ->with('success', 'Group Customer berhasil diupdate.');
    }

    public function destroy($fgroupid)
    {
        // Mengambil data grup customer berdasarkan ID
        $groupCustomer = Groupcustomer::findOrFail($fgroupid);

        // Menghapus data grup customer
        $groupCustomer->delete();

        // Mengarahkan kembali dengan pesan sukses
        return redirect()->route('groupcustomer.index')
            ->with('success', 'Group Customer berhasil dihapus.');
    }
}
