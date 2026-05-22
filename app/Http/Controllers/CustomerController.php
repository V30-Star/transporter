<?php

namespace App\Http\Controllers;

use App\Models\Customer;  // Assuming you have a Customer model
use App\Models\Groupcustomer;
// Add this import to get the groups
use App\Models\Rekening;  // Add this import to get the groups
use App\Models\Salesman;  // Add this import to get the groups
use App\Models\Wilayah;  // Add this import to get the groups
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    // Index method to list all customers with search functionality
    public function index(Request $request)
    {
        // Ambil permissions dulu
        $canCreate = in_array('createCustomer', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateCustomer', explode(',', session('user_restricted_permissions', '')));
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
                'w.fwilayahname',
            ];

            // Handle Search
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }

            // Pencarian per kolom
            $columnFields = [
                'mscustomer.fcustomercode',
                'mscustomer.fcustomername',
                'w.fwilayahname',
                'mscustomer.faddress',
            ];

            foreach ($columnFields as $index => $field) {
                $colSearch = $request->input("columns.{$index}.search.value");
                if ($colSearch !== null && $colSearch !== '') {
                    $query->where($field, 'ilike', "%{$colSearch}%");
                }
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
                6 => null, // Kolom 'Actions'
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
                $isActive = (string) $customer->fnonactive === '0';
                $statusBadge = $isActive
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Active</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-200 text-red-700">Non Active</span>';

                // Kita TIDAK buat $actions di sini.
                // Kita hanya kirim ID-nya.
                return [
                    'fcustomercode' => $customer->fcustomercode,
                    'fcustomername' => $customer->fcustomername,
                    'wilayah_name' => $customer->wilayah_name,
                    'faddress' => $customer->faddress,
                    'ftempo' => $customer->ftempo,
                    'status' => $statusBadge,
                    'fcustomerid' => $customer->fcustomerid, // KIRIM ID INI
                    'DT_RowId' => 'row_'.$customer->fcustomerid,
                ];
            });
            // --- AKHIR PERUBAHAN ---

            // Kirim response JSON
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        // --- Handle Request non-AJAX (Saat load halaman pertama kali) ---
        $status = $request->query('status');

        return view('customer.index', compact(
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
            ->orderByRaw('CAST(SUBSTRING(fcustomercode FROM 3) AS INTEGER) DESC')
            ->value('fcustomercode');

        if (! $lastCode) {
            return 'C-01';
        }

        $number = (int) substr($lastCode, 2);
        $newNumber = $number + 1;

        return 'C-'.str_pad($newNumber, 2, '0', STR_PAD_LEFT);
    }

    // Create method to return the customer creation form
    public function create()
    {
        $groups = Groupcustomer::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('customer.create', compact('groups', 'salesman', 'wilayah', 'rekening', 'newCustomerCode'));
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
            'fnik' => 'required_without:fnpwp|nullable|string|prohibits:fnpwp',
        ], [
            'fcustomercode.max' => 'Kode customer max 10 karakter.',
            'fcustomername.required' => 'Nama customer wajib diisi.',
            'fgroup.required' => 'Group produk wajib dipilih.',
            'fsalesman.required' => 'Salesman wajib dipilih.',
            'fwilayah.required' => 'Wilayah wajib dipilih.',
            'fjadwaltukarfakturmingguan.required' => 'Jadwal tukar faktur wajib dipilih.',
            'fjadwaltukarfakturhari.required' => 'Hari tukar faktur wajib dipilih.',
            'fkodefp.required' => 'Kode FP wajib diisi.',
            'ftelp.required' => 'Telepon wajib diisi.',
            'ffax.required' => 'Fax wajib diisi.',
            'femail.required' => 'Email wajib diisi.',
            'ftempo.required' => 'Tempo wajib diisi.',
            'fmaxtempo.required' => 'Max tempo wajib diisi.',
            'flimit.required' => 'Limit wajib diisi.',
            'faddress.required' => 'Alamat wajib diisi.',
            'fkirimaddress1.required' => 'Alamat kirim 1 wajib diisi.',
            'fkirimaddress2.required' => 'Alamat kirim 2 wajib diisi.',
            'fkirimaddress3.required' => 'Alamat kirim 3 wajib diisi.',
            'ftaxaddress.required' => 'Alamat pajak wajib diisi.',
            'fhargalevel.required' => 'Level harga wajib dipilih.',
            'fkontakperson.required' => 'Kontak person wajib diisi.',
            'fjabatan.required' => 'Jabatan wajib diisi.',
            'frekening.required' => 'Rekening wajib dipilih.',
            'fcustomercode.unique' => 'Kode customer sudah ada.',
            'fnpwp.required_without' => 'Isi salah satu: NPWP atau NIK.',
            'fnik.required_without' => 'Isi salah satu: NPWP atau NIK.',
            'fnpwp.prohibits' => 'Hanya boleh isi NPWP atau NIK.',
            'fnik.prohibits' => 'Hanya boleh isi NPWP atau NIK.',
        ]);

        if (empty($request->fcustomercode)) {
            $validated['fcustomercode'] = $this->generateCustomerCode();
        }

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fblokir'] = $request->has('fblokir') ? '1' : '0';

        $validated['fcurrency'] = 'IDR';

        Customer::create($validated);

        return redirect()
            ->route('customer.create')
            ->with('success', 'Customer berhasil disimpan.');
    }

    // Edit method to return the customer edit form with existing data
    public function edit($fcustomerid)
    {
        // Find Customer by primary key
        $customer = Customer::findOrFail($fcustomerid);
        $isTransactionLocked = $this->hasTransactionUsage($customer);
        $groups = Groupcustomer::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('customer.edit', [
            'customer' => $customer,
            'groups' => $groups,
            'salesman' => $salesman,
            'wilayah' => $wilayah,
            'rekening' => $rekening,
            'newCustomerCode' => $newCustomerCode,
            'isTransactionLocked' => $isTransactionLocked,
            'action' => 'edit',
        ]);
    }

    public function view($fcustomerid)
    {
        // Find Customer by primary key
        $customer = Customer::findOrFail($fcustomerid);
        $groups = Groupcustomer::where('fnonactive', 0)->get();
        $salesman = Salesman::where('fnonactive', 0)->get();
        $wilayah = Wilayah::where('fnonactive', 0)->get();
        $rekening = Rekening::where('fnonactive', 0)->get();
        $newCustomerCode = $this->generateCustomerCode();

        return view('customer.view', [
            'customer' => $customer,
            'groups' => $groups,
            'salesman' => $salesman,
            'wilayah' => $wilayah,
            'rekening' => $rekening,
            'newCustomerCode' => $newCustomerCode,
        ]);
    }

    // Update method to save the updated customer data in the database
    public function update(Request $request, $fcustomerid)
    {
        // 1. Ambil data customer yang lama
        $customer = Customer::findOrFail($fcustomerid);
        $isTransactionLocked = $this->hasTransactionUsage($customer);

        // 2. LOGIKA PENANGANAN fcustomercode

        // Kondisi: Jika input 'fcustomercode' kosong atau NULL
        if ($isTransactionLocked || empty($request->fcustomercode)) {

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
            'fcustomercode' => 'required|string|max:10|unique:mscustomer,fcustomercode,'.$fcustomerid.',fcustomerid',
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
            'fnik' => 'required_without:fnpwp|nullable|string|prohibits:fnpwp',
        ], [
            'fcustomername.required' => 'Nama customer wajib diisi.',
            'fgroup.required' => 'Group produk wajib dipilih.',
            'fsalesman.required' => 'Salesman wajib dipilih.',
            'fwilayah.required' => 'Wilayah wajib dipilih.',
            'fnpwp.required' => 'NPWP wajib diisi.',
            'fnik.required' => 'NIK wajib diisi.',
            'fjadwaltukarfakturmingguan.required' => 'Jadwal tukar faktur wajib dipilih.',
            'fjadwaltukarfakturhari.required' => 'Hari tukar faktur wajib dipilih.',
            'fkodefp.required' => 'Kode FP wajib diisi.',
            'ftelp.required' => 'Telepon wajib diisi.',
            'ffax.required' => 'Fax wajib diisi.',
            'femail.required' => 'Email wajib diisi.',
            'ftempo.required' => 'Tempo wajib diisi.',
            'fmaxtempo.required' => 'Max tempo wajib diisi.',
            'flimit.required' => 'Limit wajib diisi.',
            'faddress.required' => 'Alamat wajib diisi.',
            'fkirimaddress1.required' => 'Alamat kirim 1 wajib diisi.',
            'fkirimaddress2.required' => 'Alamat kirim 2 wajib diisi.',
            'fkirimaddress3.required' => 'Alamat kirim 3 wajib diisi.',
            'ftaxaddress.required' => 'Alamat pajak wajib diisi.',
            'fhargalevel.required' => 'Level harga wajib dipilih.',
            'fkontakperson.required' => 'Kontak person wajib diisi.',
            'fjabatan.required' => 'Jabatan wajib diisi.',
            'frekening.required' => 'Rekening wajib dipilih.',
            'fcustomercode.unique' => 'Kode customer sudah ada.',
            'fnpwp.prohibits' => 'Hanya boleh isi NPWP atau NIK.',
            'fnik.prohibits' => 'Hanya boleh isi NPWP atau NIK.',
        ]);
        $customer = Customer::findOrFail($fcustomerid);

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now();

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $validated['fcurrency'] = 'IDR';
        if ($isTransactionLocked) {
            $validated['fcustomercode'] = $customer->fcustomercode;
        }
        $customer->update($validated);

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer berhasil diupdate.');
    }

    public function delete($fcustomerid)
    {
        $customer = Customer::findOrFail($fcustomerid);

        if ($message = $this->getUsageLockMessage($customer)) {
            return redirect()->route('customer.view', $customer->fcustomerid)->with('error', $message);
        }

        return view('customer.delete', [
            'customer' => $customer,
        ]);
    }

    public function destroy($fcustomerid)
    {
        try {
            $customer = Customer::findOrFail($fcustomerid);

            if ($message = $this->getUsageLockMessage($customer)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('customer.view', $customer->fcustomerid),
                    ], 422);
                }

                return redirect()->route('customer.view', $customer->fcustomerid)->with('error', $message);
            }

            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer '.$customer->fcustomername.' berhasil dihapus.',
                'redirect' => route('customer.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer belum bisa dihapus. Coba lagi.',
            ], 500);
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
        $query = DB::table('mscustomer')
            ->leftJoin('mswilayah as w', 'w.fwilayahid', '=', 'mscustomer.fwilayah')
            ->select(
                'mscustomer.fcustomerid',
                'mscustomer.fcustomercode',
                'mscustomer.fcustomername',
                'mscustomer.faddress',
                'mscustomer.ftempo',
                'mscustomer.fsalesman',
                'mscustomer.fkirimaddress1',
                'mscustomer.fkirimaddress2',
                'mscustomer.fkirimaddress3',
                'mscustomer.ftelp',
                'mscustomer.fnonactive',
                'w.fwilayahname as wilayah_name'
            );

        $recordsTotal = DB::table('mscustomer')->count();

        $rawSearch = data_get($request->all(), 'search.value', $request->input('search', ''));
        if (is_array($rawSearch)) {
            $rawSearch = reset($rawSearch);
        }

        $search = trim((string) $rawSearch);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('fcustomercode', 'ilike', "%{$search}%")
                    ->orWhere('fcustomername', 'ilike', "%{$search}%")
                    ->orWhere('faddress', 'ilike', "%{$search}%")
                    ->orWhere('ftelp', 'ilike', "%{$search}%")
                    ->orWhere('w.fwilayahname', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = (string) $request->input('order_column', 'fcustomername');
        $orderDir = strtolower((string) $request->input('order_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedColumns = ['fcustomercode', 'fcustomername', 'faddress', 'ftelp', 'wilayah_name'];
        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('fcustomername', 'asc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)
            ->take($length)
            ->get()
            ->map(function ($customer) {
                $isActive = (string) ($customer->fnonactive ?? '0') === '0';

                return [
                    'fcustomerid' => (int) $customer->fcustomerid,
                    'fcustomercode' => (string) ($customer->fcustomercode ?? ''),
                    'fcustomername' => (string) ($customer->fcustomername ?? ''),
                    'faddress' => (string) ($customer->faddress ?? ''),
                    'ftempo' => (string) ($customer->ftempo ?? '0'),
                    'fsalesman' => (string) ($customer->fsalesman ?? ''),
                    'fkirimaddress1' => (string) ($customer->fkirimaddress1 ?? ''),
                    'fkirimaddress2' => (string) ($customer->fkirimaddress2 ?? ''),
                    'fkirimaddress3' => (string) ($customer->fkirimaddress3 ?? ''),
                    'ftelp' => (string) ($customer->ftelp ?? ''),
                    'fnonactive' => (string) ($customer->fnonactive ?? '0'),
                    'wilayah_name' => (string) ($customer->wilayah_name ?? ''),
                    'status' => $isActive
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Active</span>'
                        : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-200 text-red-700">Non Active</span>',
                ];
            })
            ->values();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function hasTransactionUsage(Customer $customer): bool
    {
        $customerCode = trim((string) $customer->fcustomercode);

        if ($customerCode === '') {
            return false;
        }

        return DB::table('trsomt')->where('fcustno', $customerCode)->exists()
            || DB::table('tranmt')->where('fcustno', $customerCode)->exists()
            || DB::table('trstockmt')->where('fsupplier', $customerCode)->exists();
    }

    private function getUsageLockMessage(Customer $customer): ?string
    {
        if (! $this->hasTransactionUsage($customer)) {
            return null;
        }

        return 'Customer ' . strtoupper((string) $customer->fcustomercode) . ' tidak bisa dihapus. Sudah direferensi di transaksi.';
    }
}
