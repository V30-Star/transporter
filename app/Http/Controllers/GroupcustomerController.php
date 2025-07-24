<?php

namespace App\Http\Controllers;

use App\Models\Groupcustomer;
use Illuminate\Http\Request;

class GroupcustomerController extends Controller
{
    public function index(Request $request)
    {
        // Ambil parameter pencarian jika ada
        $search = $request->input('search');

        // Ambil data grup customer dengan pencarian
        $groupCustomers = Groupcustomer::search($search)->paginate(10);

        // Kembalikan ke view dengan data grup customer dan pencarian
        return view('master.groupcustomer.index', compact('groupCustomers', 'search'));
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
        ]);

        // Menambahkan nilai default untuk kolom yang tidak ada dalam form
        $validated['fcreatedby'] = 'User yang membuat'; // bisa diganti dengan user yang sedang login
        $validated['fupdatedby'] = 'User yang membuat'; // bisa diganti dengan user yang sedang login
        $validated['fcreatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fupdatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0'; // Menangani checkbox

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
        // Validasi input yang diterima dari form
        $validated = $request->validate([
            'fgroupcode' => "required|string|unique:msgroupcustomer,fgroupcode,{$fgroupid},fgroupid",
            'fgroupname' => 'required|string',
        ]);

        // Menambahkan nilai default untuk kolom yang tidak ada dalam form
        $validated['fupdatedby'] = 'User yang mengupdate'; // bisa diganti dengan user yang sedang login
        $validated['fupdatedat'] = now(); // Menggunakan waktu sekarang
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0'; // Menangani checkbox

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
