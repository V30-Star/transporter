<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['faccount', 'faccname', 'faccid', 'fnormal', 'fend', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'faccount';
        $sortDir = $request->sort_dir === 'desc' ? 'desc' : 'asc';

        $status = $request->query('status');
        $year = $request->query('year');
        $month = $request->query('month');

        $query = Account::query();

        // Filter status
        if ($status === 'active') {
            $query->where('fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('fnonactive', '1');
        }

        // Filter tahun (PostgreSQL syntax)
        if ($year) {
            $query->whereRaw('EXTRACT(YEAR FROM fcreatedat) = ?', [$year]);
        }

        // Filter bulan (PostgreSQL syntax)
        if ($month) {
            $query->whereRaw('EXTRACT(MONTH FROM fcreatedat) = ?', [$month]);
        }

        $accounts = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['faccount', 'faccname', 'faccid', 'fnormal', 'fend', 'fnonactive', 'fcreatedat']);

        // Ambil tahun-tahun yang tersedia dari data (PostgreSQL syntax)
        $availableYears = Account::selectRaw('DISTINCT EXTRACT(YEAR FROM fcreatedat) as year')
            ->whereNotNull('fcreatedat')
            ->orderByRaw('EXTRACT(YEAR FROM fcreatedat) DESC')
            ->pluck('year');

        $canCreate = in_array('createAccount', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateAccount', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteAccount', explode(',', session('user_restricted_permissions', '')));

        return view('account.index', compact('accounts', 'canCreate', 'canEdit', 'canDelete', 'status', 'availableYears', 'year', 'month'));
    }

    public function create()
    {
        $accounts = Account::where('fend', 0) // header
            ->orderBy('faccount')
            ->limit(50)
            ->get();
        return view('account.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'faccount' => strtoupper($request->faccount),
            'faccname' => strtoupper($request->faccname),
        ]);
        $validated = $request->validate(
            [
                'faccount'     => 'required|string|unique:account,faccount|max:10',
                'faccname'     => 'required|string',
                'faccupline'   => 'nullable|integer', // <— ganti ke faccid
                'finitjurnal'  => 'nullable|string|max:2',
                'fnormal'      => 'required|in:D',
                'fend'         => 'required|in:1,0',
                'fuserlevel'   => 'required|in:1,2,3',
                'fhavesubaccount'  => 'sometimes|boolean',
                'ftypesubaccount'  => 'nullable|in:Sub Account,Customer,Supplier',
            ],
            [
                'faccount.required' => 'Kode account harus diisi.',
                'faccname.required' => 'Nama account harus diisi.',
                'faccount.unique'   => 'Kode account sudah ada.',
                'faccount.max'      => 'Kode account maksimal 10 karakter.',
                'faccname.max'      => 'Nama account maksimal 50 karakter.',
                'finitjurnal.max'   => 'Inisial jurnal maksimal 2 karakter.',
                'faccupline.exists' => 'Account header tidak valid.',
            ]
        );

        $validated['faccount'] = strtoupper($validated['faccount']);
        $validated['faccname'] = strtoupper($validated['faccname']);

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';
        $validated['fcreatedat'] = now();

        // Non aktif (checkbox)
        $validated['fnonactive'] = $request->boolean('fnonactive_checkbox') ? '1' : '0';

        // Sub account
        $hasSub = $request->boolean('fhavesubaccount');
        $validated['fhavesubaccount'] = $hasSub ? 1 : 0;
        $validated['ftypesubaccount'] = $hasSub
            ? ($request->input('ftypesubaccount') === 'Sub Account' ? 'S' : ($request->input('ftypesubaccount') === 'Customer' ? 'C' : 'P'))
            : '0';

        // Map select lain
        $validated['fnormal']    = $request->input('fnormal');
        $validated['fend']       = $request->input('fend');
        // faccupline sudah dari hidden (id header)
        // currency tetap IDR
        $validated['fcurrency']  = 'IDR';

        Account::create($validated);

        return redirect()->route('account.create')->with('success', 'Account berhasil ditambahkan.');
    }

    public function edit($faccid)
    {
        $account = Account::findOrFail($faccid);

        // preload 50 header untuk dropdown view
        $headers = Account::where('fend', 0)
            ->orderBy('faccount')
            ->limit(50)
            ->get();

        // header yang sedang terset di record ini (jika ada)
        $selectedHeader = null;
        if (!empty($account->faccupline)) {
            $selectedHeader = Account::find($account->faccupline);
        }

        return view('account.edit', [
            'account' => $account,
            'headers' => $headers,
            'selectedHeader' => $selectedHeader,
            'action' => 'edit' // Tambahkan ini
        ]);
    }

    public function update(Request $request, $faccid)
    {
        $request->merge([
            'faccount' => strtoupper($request->faccount),
        ]);

        $validated = $request->validate(
            [
                'faccount'     => "required|string|unique:account,faccount,{$faccid},faccid|max:10",
                'faccname'     => 'required|string|max:50',
                'fnormal'      => 'required|in:D',
                'finitjurnal'  => 'nullable|string|max:2',
                'fend'         => 'required|in:1,0',
                'fuserlevel'   => 'required|in:1,2,3',
                'faccupline'   => [
                    'nullable',
                    'integer',
                    // Rule::exists('account', 'faccid')->where(fn($q) => $q->where('fend', 0)),
                    // Rule::notIn([$faccid]),
                ],
            ],
            [
                'faccount.required' => 'Kode account harus diisi.',
                'faccount.unique'   => 'Kode account sudah ada.',
                'faccount.max'      => 'Kode account maksimal 10 karakter.',
                'faccname.required' => 'Nama account harus diisi.',
                'faccname.max'      => 'Nama account maksimal 50 karakter.',
                'finitjurnal.max'   => 'Inisial jurnal maksimal 2 karakter.',
                'faccupline.exists' => 'Account header tidak valid.',
            ]
        );

        $validated['faccount'] = strtoupper($validated['faccount']);
        $validated['faccname'] = strtoupper($validated['faccname']);

        // Checkbox & metadata
        $validated['fnonactive']  = $request->has('fnonactive') ? '1' : '0';
        $validated['fupdatedby']  = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat']  = now();

        // Sub account
        $validated['fhavesubaccount'] = $request->has('fhavesubaccount') ? 1 : 0;
        $validated['ftypesubaccount'] = $validated['fhavesubaccount']
            ? ($request->input('ftypesubaccount') === 'Sub Account' ? 'S'
                : ($request->input('ftypesubaccount') === 'Customer' ? 'C' : 'P'))
            : '0';

        // Map select lain
        $validated['fnormal']   = $request->input('fnormal');
        $validated['fend']      = $request->input('fend');
        $validated['fuserlevel'] = $request->input('fuserlevel');
        $validated['fcurrency'] = 'IDR';

        // PENTING: simpan faccupline (boleh null)
        $validated['faccupline'] = $request->filled('faccupline')
            ? (int) $request->input('faccupline')
            : null;

        $account = Account::findOrFail($faccid);
        $account->update($validated);

        return redirect()->route('account.index')->with('success', 'Account berhasil di-update.');
    }
    public function delete($faccid)
    {
        $account = Account::findOrFail($faccid);

        return view('account.edit', [
            'account' => $account,
            'headers' => [], // Tidak perlu headers untuk delete
            'selectedHeader' => null,
            'action' => 'delete' // Tambahkan ini
        ]);
    }

    public function destroy($faccid)
    {
        // Find Account
        $account = Account::findOrFail($faccid);

        // --- Validation Check ---

        // 1. Cek apakah kolom 'fend' bernilai 0
        if ($account->fend == 0) {
            return redirect()
                ->route('account.index')
                ->with('error', 'Account tidak dapat dihapus karena Header.');
        }

        // 2. Cek apakah account ini memiliki sub-account (child account)
        $hasChildren = Account::where('faccupline', $faccid)->exists();

        if ($hasChildren) {
            return redirect()
                ->route('account.index')
                ->with('error', 'Account tidak dapat dihapus karena memiliki sub-account.');
        }

        // 3. (Opsional) Cek apakah account sudah digunakan di transaksi
        // Uncomment jika Anda ingin cek di tabel jurnal/transaksi
        $usedInTransaction = DB::table('jurnaldt')->where('faccount', $account->faccount)->exists();

        if ($usedInTransaction) {
            return redirect()
                ->route('account.index')
                ->with('error', 'Account tidak dapat dihapus karena sudah digunakan dalam transaksi.');
        }

        // --- End Validation Check ---

        // Delete the Account
        $account->delete();
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Account berhasil dihapus.'
            ]);
        }

        return redirect()
            ->route('account.index')
            ->with('success', 'Account berhasil dihapus.');
    }

    public function suggestAccounts(Request $request)
    {
        $term = (string) $request->get('term', '');
        $field = $request->get('field', 'faccount'); // faccount atau faccname

        $q = DB::table('account')
            ->whereNotNull('faccount')
            ->whereNotNull('faccname');

        if ($term !== '') {
            if ($field === 'faccount') {
                $q->where('faccount', 'ILIKE', "%{$term}%");
            } else {
                $q->where('faccname', 'ILIKE', "%{$term}%");
            }
        }

        // Return data dengan format: {value, label, code, name}
        $accounts = $q->select('faccount', 'faccname')
            ->distinct()
            ->orderBy($field)
            ->limit(15)
            ->get()
            ->map(function ($item) use ($field) {
                return [
                    'value' => $field === 'faccount' ? $item->faccount : $item->faccname,
                    'label' => $field === 'faccount'
                        ? "{$item->faccount} - {$item->faccname}"
                        : "{$item->faccname} ({$item->faccount})",
                    'code' => $item->faccount,
                    'name' => $item->faccname
                ];
            });

        return response()->json($accounts);
    }

    public function browse(Request $request)
    {
        // Base query
        $query = Account::query();

        // Filter awal: hanya tampilkan akun yang tidak berakhir (fend = 0)
        $query->where('fend', 0);

        // Total records tanpa filter (sesuai filter base 'fend = 0')
        $recordsTotal = Account::where('fend', 0)->count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('faccount', 'ilike', "%{$search}%")
                    ->orWhere('faccname', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting (Disamakan dengan Supplier)
        $orderColumn = $request->input('order_column', 'faccname');
        $orderDir = $request->input('order_dir', 'asc');

        // Kolom yang diizinkan untuk di-sorting
        $allowedColumns = ['faccount', 'faccname'];

        if (in_array($orderColumn, $allowedColumns)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            // Default order
            $query->orderBy('faccname', 'asc');
        }

        // Pagination (Menggunakan start dan length dari DataTables)
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
