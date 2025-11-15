<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function browse(Request $request)
    {
        $accounts = Account::select('faccid as id', 'faccount', 'faccname') // ← PASTIKAN faccid ada
            ->when($request->search, function ($q, $search) {
                $q->where('faccount', 'like', "%{$search}%")
                    ->orWhere('faccname', 'like', "%{$search}%");
            })
            ->paginate($request->per_page ?? 10);

        return response()->json($accounts);
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
        $validated = $request->validate(
            [
                'faccount'     => 'required|string|unique:account,faccount|max:10',
                'faccname'     => 'required|string|max:50',
                'faccupline'  => 'nullable|integer|exists:account,faccid', // <— ganti ke faccid
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
        $account  = Account::findOrFail($faccid);

        // preload 50 header untuk dropdown view
        $headers  = Account::where('fend', 0)
            ->orderBy('faccount')
            ->limit(50)
            ->get();

        // header yang sedang terset di record ini (jika ada)
        $selectedHeader = null;
        if (!empty($account->faccupline)) {
            $selectedHeader = Account::find($account->faccupline); // faccupline menyimpan faccid header
        }

        return view('account.edit', compact('account', 'headers', 'selectedHeader'));
    }

    public function update(Request $request, $faccid)
    {
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
                    Rule::exists('account', 'faccid')->where(fn($q) => $q->where('fend', 0)),
                    Rule::notIn([$faccid]),
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

    public function destroy($faccid)
    {
        // Find Account and delete it
        $account = Account::findOrFail($faccid);
        $account->delete();

        return redirect()
            ->route('account.index')
            ->with('success', 'Account berhasil dihapus.');
    }
}
