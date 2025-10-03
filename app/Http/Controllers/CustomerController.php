<?php

namespace App\Http\Controllers;

use App\Models\Customer;  // Assuming you have a Customer model
use App\Models\Groupproduct;  // Add this import to get the groups
use App\Models\Salesman;  // Add this import to get the groups
use App\Models\Wilayah;  // Add this import to get the groups
use App\Models\Rekening;  // Add this import to get the groups
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Index method to list all customers with search functionality
    public function index(Request $request)
    {
        $allowedSorts = ['fcustomercode', 'fcustomername', 'fcustomerid', 'faddress', 'faddress', 'faddress', 'ftempo', 'ftempo', 'fkirimaddress1', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fcustomerid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $status = $request->query('status');

        $query = Customer::query();

        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        $customers = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fcustomercode', 'fcustomername', 'fcustomerid', 'faddress', 'faddress', 'faddress', 'ftempo', 'ftempo', 'fkirimaddress1', 'fnonactive']);

        $canCreate = in_array('createCustomer', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateCustomer', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteCustomer', explode(',', session('user_restricted_permissions', '')));

        return view('master.customer.index', compact('customers', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    private function generateCustomerCode(): string
    {
        $lastCode = Customer::where('fcustomercode', 'like', 'C-%')
            ->orderByRaw("CAST(SUBSTRING(fcustomercode FROM 3) AS INTEGER) DESC")
            ->value('fcustomercode');

        if (!$lastCode) {
            return 'C-01';
        }

        $number = (int)substr($lastCode, 2);
        $newNumber = $number + 1;

        return 'C-' . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
    }

    // Create method to return the customer creation form
    public function create()
    {
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('master.customer.create', compact('groups', 'salesman', 'wilayah', 'rekening', 'newCustomerCode'));
    }

    // Store method to save the new customer in the database
    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'fcustomercode' => 'nullable|string|max:10',  // Validate customer code (max 10 chars)
            'fcustomername' => 'required|string|max:50', // Validate customer name (max 50 chars)
            'fgroup' => 'required', // Validate the Group Produk field
            'fsalesman' => 'required', // Validate the Group Produk field
            'fwilayah' => 'required', // Validate the Group Produk field
            'fnpwp' => 'required', // Validate the Group Produk field
            'fnik' => 'required', // Validate the Group Produk field
            'fjadwaltukarfaktur' => 'required|in:Setiap Minggu,Setiap Bulan,Sesuai Permintaan', // Validate Satuan Default field
            'fkodefp' => 'required', // Validate Satuan Default field
            'ftelp' => 'required', // Validate Satuan Default field
            'ffax' => 'required', // Validate Satuan Default field
            'femail' => 'required', // Validate Satuan Default field
            'ftempo' => 'required', // Validate Satuan Default field
            'fmaxtempo' => 'required', // Validate Satuan Default field
            'flimit' => 'required', // Validate Satuan Default field
            'faddress' => 'required', // Validate Satuan Default field
            'fkirimaddress1' => 'required', // Validate Satuan Default field
            'fkirimaddress2' => 'required', // Validate Satuan Default field
            'fkirimaddress3' => 'required', // Validate Satuan Default field
            'ftaxaddress' => 'required', // Validate Satuan Default field
            'fhargalevel' => 'required|in:0,1,2', // Validate Satuan Default field
            'fkontakperson' => 'required', // Validate Satuan Default field
            'fjabatan' => 'required', // Validate Satuan Default field
            'frekening' => 'required', // Validate Satuan Default field
            'fmemo' => '', // Validate Satuan Default field
        ], [
            'fcustomercode.max' => 'Kode Customer tidak boleh lebih dari 10 karakter.',
            'fcustomername.required' => 'Nama Customer harus diisi.',
            'fgroup.required' => 'Group Produk harus dipilih.',
            'fsalesman.required' => 'Salesman harus dipilih.',
            'fwilayah.required' => 'Wilayah harus dipilih.',
            'fnpwp.required' => 'NPWP harus diisi.',
            'fnik.required' => 'NIK harus diisi.',
            'fjadwaltukarfaktur.required' => 'Jadwal Tukar Faktur harus dipilih.',
            'fkodefp.required' => 'Kode FP harus diisi.',
            'ftelp.required' => 'Telepon harus diisi.',
            'ffax.required' => 'Fax harus diisi.',
            'femail.required' => 'Email harus diisi.',
            'ftempo.required' => 'Tempo harus diisi.',
            'fmaxtempo.required' => 'Maksimal Tempo harus diisi.',
            'flimit.required' => 'Limit harus diisi.',
            'faddress.required' => 'Alamat harus diisi.',
            'fkirimaddress1.required' => 'Alamat Kirim 1 harus diisi.',
            'fkirimaddress2.required' => 'Alamat Kirim 2 harus diisi.',
            'fkirimaddress3.required' => 'Alamat Kirim 3 harus diisi.',
            'ftaxaddress.required' => 'Alamat Pajak harus diisi.',
            'fhargalevel.required' => 'Level Harga harus dipilih.',
            'fkontakperson.required' => 'Kontak Person harus diisi.',
            'fjabatan.required' => 'Jabatan Kontak Person harus diisi.',
            'frekening.required' => 'Rekening harus dipilih.'
        ]);

        if (empty($request->fcustomercode)) {
            $validated['fcustomercode'] = $this->generateCustomerCode();
        }

        $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fcurrency'] = 'IDR';

        Customer::create($validated);

        return redirect()
            ->route('customer.create')
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    // Edit method to return the customer edit form with existing data
    public function edit($fcustomerid)
    {
        // Find Customer by primary key
        $customer = Customer::findOrFail($fcustomerid);
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('master.customer.edit', compact('customer', 'groups', 'salesman', 'wilayah', 'rekening', 'newCustomerCode'));
    }

    // Update method to save the updated customer data in the database
    public function update(Request $request, $fcustomerid)
    {
        $validated = $request->validate([
            'fcustomername' => 'required|string|max:50', // Validate customer name (max 50 chars)
            'fgroup' => 'required', // Validate the Group Produk field
            'fsalesman' => 'required', // Validate the Group Produk field
            'fwilayah' => 'required', // Validate the Group Produk field
            'fnpwp' => 'required', // Validate the Group Produk field
            'fnik' => 'required', // Validate the Group Produk field
            'fjadwaltukarfaktur' => 'required|in:Setiap Minggu,Setiap Bulan,Sesuai Permintaan', // Validate Satuan Default field
            'fkodefp' => 'required', // Validate Satuan Default field
            'ftelp' => 'required', // Validate Satuan Default field
            'ffax' => 'required', // Validate Satuan Default field
            'femail' => 'required', // Validate Satuan Default field
            'ftempo' => 'required', // Validate Satuan Default field
            'fmaxtempo' => 'required', // Validate Satuan Default field
            'flimit' => 'required', // Validate Satuan Default field
            'faddress' => 'required', // Validate Satuan Default field
            'fkirimaddress1' => 'required', // Validate Satuan Default field
            'fkirimaddress2' => 'required', // Validate Satuan Default field
            'fkirimaddress3' => 'required', // Validate Satuan Default field
            'ftaxaddress' => 'required', // Validate Satuan Default field
            'fhargalevel' => 'required|in:0,1,2', // Validate Satuan Default field
            'fkontakperson' => 'required', // Validate Satuan Default field
            'fjabatan' => 'required', // Validate Satuan Default field
            'frekening' => 'required', // Validate Satuan Default field
            'fmemo' => '', // Validate Satuan Default field
        ], [
            'fcustomername.required' => 'Nama Customer harus diisi.',
            'fgroup.required' => 'Group Produk harus dipilih.',
            'fsalesman.required' => 'Salesman harus dipilih.',
            'fwilayah.required' => 'Wilayah harus dipilih.',
            'fnpwp.required' => 'NPWP harus diisi.',
            'fnik.required' => 'NIK harus diisi.',
            'fjadwaltukarfaktur.required' => 'Jadwal Tukar Faktur harus dipilih.',
            'fkodefp.required' => 'Kode FP harus diisi.',
            'ftelp.required' => 'Telepon harus diisi.',
            'ffax.required' => 'Fax harus diisi.',
            'femail.required' => 'Email harus diisi.',
            'ftempo.required' => 'Tempo harus diisi.',
            'fmaxtempo.required' => 'Maksimal Tempo harus diisi.',
            'flimit.required' => 'Limit harus diisi.',
            'faddress.required' => 'Alamat harus diisi.',
            'fkirimaddress1.required' => 'Alamat Kirim 1 harus diisi.',
            'fkirimaddress2.required' => 'Alamat Kirim 2 harus diisi.',
            'fkirimaddress3.required' => 'Alamat Kirim 3 harus diisi.',
            'ftaxaddress.required' => 'Alamat Pajak harus diisi.',
            'fhargalevel.required' => 'Level Harga harus dipilih.',
            'fkontakperson.required' => 'Kontak Person harus diisi.',
            'fjabatan.required' => 'Jabatan Kontak Person harus diisi.',
            'frekening.required' => 'Rekening harus dipilih.'
        ]);
        $customer = Customer::findOrFail($fcustomerid);
        $validated['fcustomercode'] = $request->fcustomercode ?? $customer->fcustomercode;

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        if ($request->has('approve_now')) {
            $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;
        } else {
            $validated['fapproval'] = null;
        }

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fcurrency'] = 'IDR';
        $customer->update($validated);

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer berhasil di-update.');
    }

    // Destroy method to delete a customer
    public function destroy($fcustomerid)
    {
        // Find Customer and delete it
        $customer = Customer::findOrFail($fcustomerid);
        $customer->delete();

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer berhasil dihapus.');
    }
}
