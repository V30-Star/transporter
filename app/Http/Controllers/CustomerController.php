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
        // Set default filter and search query
        $filterBy = in_array($request->filter_by, ['fcustomercode', 'fcustomername'])
            ? $request->filter_by
            : 'fcustomercode';

        $search = $request->search;

        // Query with search functionality
        $customers = Customer::when($search, function($q) use ($filterBy, $search) {
                $q->where($filterBy, 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('fcustomerid', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('master.customer.index', compact('customers', 'filterBy', 'search'));
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
        $groups = Groupproduct::all();  // Fetch all group products
        $salesman = Salesman::all();  // Fetch all group products
        $wilayah = Wilayah::all();  // Fetch all group products
        $rekening = Rekening::all();  // Fetch all group products
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

            // 'femail' => 'nullable|email|max:100', // Validate email
            // 'fphone' => 'nullable|string|max:20', // Validate phone number
            // 'faddress' => 'nullable|string|max:255', // Validate address
        ]);

        if (empty($request->fcustomercode)) {
            $validated['fcustomercode'] = $this->generateCustomerCode();
        }

        // Add default values for the required fields
        $validated['fcreatedby'] = "admin";  // Use the authenticated user's ID
        $validated['fupdatedby'] = $validated['fcreatedby']; // Same for the updatedby field
        $validated['fcreatedat'] = now(); // Set current time
        $validated['fupdatedat'] = now(); // Set current time

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Directly set 'fcurrency' to 'IDR' or other applicable value
        $validated['fcurrency'] = 'IDR';

        // Create the new Customer record
        Customer::create($validated);

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    // Edit method to return the customer edit form with existing data
    public function edit($fcustomerid)
    {
        // Find Customer by primary key
        $customer = Customer::findOrFail($fcustomerid);

        return view('customer.edit', compact('customer'));
    }

    // Update method to save the updated customer data in the database
    public function update(Request $request, $fcustomerid)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'fcustomercode' => "required|string|unique:mscustomer,fcustomercode,{$fcustomerid},fcustomerid", // Exclude current customer from unique check
            'fcustomername' => 'required|string|max:50', // Validate customer name (max 50 chars)
            'femail' => 'nullable|email|max:100', // Validate email
            'fphone' => 'nullable|string|max:20', // Validate phone number
            'faddress' => 'nullable|string|max:255', // Validate address
        ]);

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = $request->has('fnonactive') ? 1 : 0;

        // Directly set 'fcurrency' to 'IDR'
        $validated['fcurrency'] = 'IDR';

        // Find Customer and update
        $customer = Customer::findOrFail($fcustomerid);
        $customer->update($validated);

        return redirect()
            ->route('master.customer.index')
            ->with('success', 'Customer berhasil di-update.');
    }

    // Destroy method to delete a customer
    public function destroy($fcustomerid)
    {
        // Find Customer and delete it
        $customer = Customer::findOrFail($fcustomerid);
        $customer->delete();

        return redirect()
            ->route('master.customer.index')
            ->with('success', 'Customer berhasil dihapus.');
    }
}
