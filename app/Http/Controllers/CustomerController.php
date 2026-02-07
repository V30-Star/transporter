<?php

namespace App\Http\Controllers;

use App\Models\Customer;  // Assuming you have a Customer model
use App\Models\Groupcustomer;
use App\Models\Groupproduct;  // Add this import to get the groups
use App\Models\Salesman;  // Add this import to get the groups
use App\Models\Wilayah;  // Add this import to get the groups
use App\Models\Rekening;  // Add this import to get the groups
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pest\ArchPresets\Custom;

class CustomerController extends Controller
{
    // Index method to list all customers with search functionality
    public function index(Request $request)
    {
        // Ambil permissions dulu
        $canCreate = in_array('createCustomer', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateCustomer', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteCustomer', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        // --- Handle Request AJAX untuk DataTables ---
        if ($request->ajax()) {

            $query = DB::table('mscustomer')
                ->leftJoin('mswilayah as w', 'w.fwilayahid', '=', 'mscustomer.fwilayah');

            // Filter Status
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('mscustomer.fnonactive', '0');
            } elseif ($status === 'nonactive') {
                $query->where('mscustomer.fnonactive', '1');
            }

            $totalRecords = DB::table('mscustomer')->count();

            // Kolom yang bisa dicari
            $searchableColumns = [
                'mscustomer.fcustomercode',
                'mscustomer.fcustomername',
                'mscustomer.faddress',
                'w.fwilayahname'
            ];

            // Handle Search
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }

            $filteredRecords = (clone $query)->count();

            // Sorting
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');
            $columns = [
                0 => 'mscustomer.fcustomercode',
                1 => 'mscustomer.fcustomername',
                2 => 'w.fwilayahname',
                3 => 'mscustomer.faddress',
                4 => 'mscustomer.ftempo',
                5 => 'mscustomer.fnonactive',
                6 => null // Kolom 'Actions'
            ];

            if (isset($columns[$orderColumnIndex]) && $columns[$orderColumnIndex] !== null) {
                $query->orderBy($columns[$orderColumnIndex], $orderDir);
            } else {
                $query->orderBy('mscustomer.fcustomercode', 'asc');
            }

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            if ($length != -1) {
                $query->skip($start)->take($length);
            }

            // Select kolom yang dibutuhkan
            $customers = $query->select(
                'mscustomer.fcustomerid', // Penting untuk ID
                'mscustomer.fcustomercode',
                'mscustomer.fcustomername',
                'mscustomer.faddress',
                'mscustomer.ftempo',
                'mscustomer.fnonactive',
                'w.fwilayahname as wilayah_name'
            )->get();

            // --- PERUBAHAN UTAMA DI SINI ---
            // Format data untuk DataTables (Pola JS Render)
            $data = $customers->map(function ($customer) {
                $isActive = (string)$customer->fnonactive === '0';
                $statusBadge = $isActive
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Active</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-200 text-red-700">Non Active</span>';

                // Kita TIDAK buat $actions di sini.
                // Kita hanya kirim ID-nya.
                return [
                    'fcustomercode' => $customer->fcustomercode,
                    'fcustomername' => $customer->fcustomername,
                    'wilayah_name'  => $customer->wilayah_name,
                    'faddress'      => $customer->faddress,
                    'ftempo'        => $customer->ftempo,
                    'status'        => $statusBadge,
                    'fcustomerid'   => $customer->fcustomerid, // KIRIM ID INI
                    'DT_RowId'      => 'row_' . $customer->fcustomerid
                ];
            });
            // --- AKHIR PERUBAHAN ---

            // Kirim response JSON
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        // --- Handle Request non-AJAX (Saat load halaman pertama kali) ---
        $status = $request->query('status');

        return view('master.customer.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            'status'
        ));
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
        $groups = Groupcustomer::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('master.customer.create', compact('groups', 'salesman', 'wilayah', 'rekening', 'newCustomerCode'));
    }

    // Store method to save the new customer in the database
    public function store(Request $request)
    {
        $request->merge([
            'fcustomercode' => strtoupper($request->fcustomercode),
        ]);
        // Validate incoming request data
        $validated = $request->validate([
            'fcustomercode' => 'nullable|string|max:10|unique:mscustomer,fcustomercode',  // Validate customer code (max 10 chars)
            'fcustomername' => 'required|string|max:50', // Validate customer name (max 50 chars)
            'fgroup' => '', // Validate the Group Produk field
            'fsalesman' => '', // Validate the Group Produk field
            'fwilayah' => '', // Validate the Group Produk field
            'fjadwaltukarfakturmingguan' => '',
            'fjadwaltukarfakturhari' => '',
            'fkodefp' => '',
            'ftelp' => '',
            'ffax' => '',
            'femail' => '',
            'ftempo' => '',
            'fmaxtempo' => '',
            'flimit' => '',
            'faddress' => '',
            'fkirimaddress1' => '',
            'fkirimaddress2' => '',
            'fkirimaddress3' => '',
            'ftaxaddress' => '',
            'fhargalevel' => '|in:0,1,2',
            'fkontakperson' => '',
            'fjabatan' => '',
            'frekening' => '',
            'fmemo' => '',
            'fnpwp' => 'required_without:fnik|nullable|string|prohibits:fnik',
            'fnik'  => 'required_without:fnpwp|nullable|string|prohibits:fnpwp',
        ], [
            'fcustomercode.max' => 'Kode Customer tidak boleh lebih dari 10 karakter.',
            'fcustomername.required' => 'Nama Customer harus diisi.',
            'fgroup.required' => 'Group Produk harus dipilih.',
            'fsalesman.required' => 'Salesman harus dipilih.',
            'fwilayah.required' => 'Wilayah harus dipilih.',
            'fjadwaltukarfakturmingguan.required' => 'Jadwal Tukar Faktur harus dipilih.',
            'fjadwaltukarfakturhari.required' => 'Hari Tukar Faktur harus dipilih.',
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
            'fjabatan.required' => 'Jabatan harus diisi.',
            'frekening.required' => 'Rekening harus dipilih.',
            'fcustomercode.unique' => 'Kode Customer ini sudah ada',
            'fnpwp.required_without' => 'Anda harus mengisi salah satu antara NPWP atau NIK.',
            'fnik.required_without'  => 'Anda harus mengisi salah satu antara NPWP atau NIK.',
            'fnpwp.prohibits'        => 'Hanya boleh mengisi satu: NPWP atau NIK saja.',
            'fnik.prohibits'         => 'Hanya boleh mengisi satu: NPWP atau NIK saja.',
        ]);

        if (empty($request->fcustomercode)) {
            $validated['fcustomercode'] = $this->generateCustomerCode();
        }

        $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fblokir'] = $request->has('fblokir') ? '1' : '0';

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
        $groups = Groupcustomer::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('master.customer.edit', [
            'customer' => $customer,
            'groups' => $groups,
            'salesman' => $salesman,
            'wilayah' => $wilayah,
            'rekening' => $rekening,
            'newCustomerCode' => $newCustomerCode,
            'action' => 'edit'
        ]);
    }

    // Update method to save the updated customer data in the database
    public function update(Request $request, $fcustomerid)
    {
        // 1. Ambil data customer yang lama
        $customer = Customer::findOrFail($fcustomerid);

        // 2. LOGIKA PENANGANAN fcustomercode

        // Kondisi: Jika input 'fcustomercode' kosong atau NULL
        if (empty($request->fcustomercode)) {

            // JIKA KOSONG: Ambil dan gunakan nilai yang lama dari database
            $request->merge([
                'fcustomercode' => $customer->fcustomercode,
            ]);
        } else {

            // JIKA DIISI: Gunakan nilai baru dari request dan ubah menjadi uppercase
            $request->merge([
                'fcustomercode' => strtoupper($request->fcustomercode),
            ]);
        }

        $validated = $request->validate([
            'fcustomercode' => 'required|string|max:10|unique:mscustomer,fcustomercode,' . $fcustomerid . ',fcustomerid',
            'fcustomername' => 'required|string|max:50', // Validate customer name (max 50 chars)
            'fgroup' => '', // Validate the Group Produk field
            'fsalesman' => '', // Validate the Group Produk field
            'fwilayah' => '', // Validate the Group Produk field
            'fjadwaltukarfakturmingguan' => '',
            'fjadwaltukarfakturhari' => '',
            'fkodefp' => '',
            'ftelp' => '',
            'ffax' => '',
            'femail' => '',
            'ftempo' => '',
            'fmaxtempo' => '',
            'flimit' => '',
            'faddress' => '',
            'fkirimaddress1' => '',
            'fkirimaddress2' => '',
            'fkirimaddress3' => '',
            'ftaxaddress' => '',
            'fhargalevel' => '|in:0,1,2',
            'fkontakperson' => '',
            'fjabatan' => '',
            'frekening' => '',
            'fmemo' => '',
            'fnpwp' => 'required_without:fnik|nullable|string|prohibits:fnik',
            'fnik'  => 'required_without:fnpwp|nullable|string|prohibits:fnpwp',
        ], [
            'fcustomername.required' => 'Nama Customer harus diisi.',
            'fgroup.required' => 'Group Produk harus dipilih.',
            'fsalesman.required' => 'Salesman harus dipilih.',
            'fwilayah.required' => 'Wilayah harus dipilih.',
            'fnpwp.required' => 'NPWP harus diisi.',
            'fnik.required' => 'NIK harus diisi.',
            'fjadwaltukarfakturmingguan.required' => 'Jadwal Tukar Faktur harus dipilih.',
            'fjadwaltukarfakturhari.required' => 'Hari Tukar Faktur harus dipilih.',
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
            'fjabatan.required' => 'Jabatan harus diisi.',
            'frekening.required' => 'Rekening harus dipilih.',
            'fcustomercode.unique' => 'Kode Customer ini sudah ada',
            'fnpwp.prohibits'        => 'Hanya boleh mengisi satu: NPWP atau NIK saja.',
            'fnik.prohibits'         => 'Hanya boleh mengisi satu: NPWP atau NIK saja.',
        ]);
        $customer = Customer::findOrFail($fcustomerid);

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

    public function delete($fcustomerid)
    {
        $customer = Customer::findOrFail($fcustomerid);
        $groups = Groupcustomer::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('master.customer.edit', [
            'customer' => $customer,
            'groups' => $groups,
            'salesman' => $salesman,
            'wilayah' => $wilayah,
            'rekening' => $rekening,
            'newCustomerCode' => $newCustomerCode,
            'action' => 'delete'
        ]);
    }

    public function destroy($fcustomerid)
    {
        try {
            $customer = Customer::findOrFail($fcustomerid);
            $customer->delete();

            return redirect()->route('customer.index')->with('success', 'Data customer ' . $customer->fcustomername . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('customer.delete', $fcustomerid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function suggestNames(Request $request)
    {
        $term = (string) $request->get('term', '');
        $q = DB::table('mscustomer')->whereNotNull('fcustomername');

        if ($term !== '') {
            $q->where('fcustomername', 'ILIKE', "%{$term}%");
        }

        $names = $q->distinct()
            ->orderBy('fcustomername')
            ->limit(10)
            ->pluck('fcustomername');

        return response()->json($names);
    }

    public function browse(Request $request)
    {
        // Base query
        $query = Customer::query();

        // Total records tanpa filter
        $recordsTotal = Customer::count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fcustomercode', 'ilike', "%{$search}%")
                    ->orWhere('fcustomername', 'ilike', "%{$search}%")
                    ->orWhere('faddress', 'ilike', "%{$search}%")
                    ->orWhere('ftelp', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fcustomername');
        $orderDir = $request->input('order_dir', 'asc');

        $allowedColumns = ['fcustomercode', 'fcustomername', 'faddress', 'ftelp'];
        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fcustomername', 'asc');
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
            'data' => $data
        ]);
    }
}
