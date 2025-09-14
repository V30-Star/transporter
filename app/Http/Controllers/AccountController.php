<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->search);
        $filterBy = $request->filter_by ?? 'all'; // all | faccount | faccname

        // Sorting
        $allowedSorts = ['faccount', 'faccname', 'faccid'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'faccount';
        $sortDir = $request->sort_dir === 'desc' ? 'desc' : 'asc';

        $accounts = Account::when($search !== '', function ($q) use ($search, $filterBy) {
            $q->where(function ($qq) use ($search, $filterBy) {
                if ($filterBy === 'faccount') {
                    $qq->where('faccount', 'ILIKE', "%{$search}%");
                } elseif ($filterBy === 'faccname') {
                    $qq->where('faccname', 'ILIKE', "%{$search}%");
                } else { // all
                    $qq->where('faccount', 'ILIKE', "%{$search}%")
                        ->orWhere('faccname', 'ILIKE', "%{$search}%");
                }
            });
        })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('faccid', 'asc') // tie-breaker stabil
            ->paginate(10)
            ->withQueryString();

        // permissions
        $canCreate = in_array('createAccount', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateAccount', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteAccount', explode(',', session('user_restricted_permissions', '')));

        if ($request->ajax()) {
            $rows = collect($accounts->items())->map(function ($a) {
                return [
                    'faccid'   => $a->faccid,
                    'faccount' => $a->faccount,
                    'faccname' => $a->faccname,
                    'fend'     => $a->fend,     // 1=Detil, 0=Header (atau sesuai schema)
                    'fnormal'  => $a->fnormal,  // 1=Debet, 0=Kredit (atau sesuai schema)
                    'edit_url'    => route('account.edit', $a->faccid),
                    'destroy_url' => route('account.destroy', $a->faccid),
                ];
            });

            return response()->json([
                'data'  => $rows,
                'perms' => ['can_create' => $canCreate, 'can_edit' => $canEdit, 'can_delete' => $canDelete],
                'links' => [
                    'prev'         => $accounts->previousPageUrl(),
                    'next'         => $accounts->nextPageUrl(),
                    'current_page' => $accounts->currentPage(),
                    'last_page'    => $accounts->lastPage(),
                ],
                'sort' => ['by' => $sortBy, 'dir' => $sortDir],
            ]);
        }

        return view('account.index', compact(
            'accounts',
            'filterBy',
            'search',
            'canCreate',
            'canEdit',
            'canDelete',
            'sortBy',
            'sortDir'
        ));
    }

    public function browse(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $perPage = (int)($request->get('per_page', 10)) ?: 10;

        // Header = 1 (sesuai skema kamu). Kalau ada historis 0, bisa whereIn([0,1]).
        $query = Account::query()->where('fend', 0);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('faccount', 'like', "%{$q}%")
                    ->orWhere('faccname', 'like', "%{$q}%");
            });
        }

        $result = $query->orderBy('faccount')->paginate($perPage);

        // penting: mapping faccid -> id agar frontend tetap pakai row.id
        $result->getCollection()->transform(function ($row) {
            return [
                'id'       => $row->faccid,     // <— pakai faccid
                'faccount' => $row->faccount,
                'faccname' => $row->faccname,
            ];
        });

        return response()->json($result);
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

        return redirect()->route('account.index')->with('success', 'Account berhasil ditambahkan.');
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
