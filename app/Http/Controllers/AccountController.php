<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        // Set default filter and search query
        $filterBy = in_array($request->filter_by, ['faccount', 'faccname'])
            ? $request->filter_by
            : 'faccount';

        $search = $request->search;

        // Query with search functionality
        $accounts = Account::when($search, function ($q) use ($filterBy, $search) {
            $q->where($filterBy, 'ILIKE', '%' . $search . '%');
        })
            ->orderBy('faccid', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('account.index', compact('accounts', 'filterBy', 'search'));
    }

    public function create()
    {
        return view('account.create');
    }

    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate(
            [
                'faccount' => 'required|string|unique:account,faccount|max:10',  // Validate account code (max 10 chars)
                'faccname' => 'required|string|max:50', // Validate account name (max 50 chars)
                'finitjurnal' => 'nullable|string|max:2', // Validate initial journal (max 2 chars)
                'fnormal' => 'required|in:1,2', // Ensure 'fnormal' is either 1 (Debet) or 2 (Kredit)
                'fend' => 'required|in:1,2', // Ensure 'fend' is either 1 (Detil) or 2 (Header)
                'fuserlevel' => 'required|in:1,2,3', // Ensure 'fuserlevel' is 1 (User), 2 (Supervisor), or 3 (Admin)
            ],
            [
                'faccount.required' => 'Kode account harus diisi.',
                'faccname.required' => 'Nama account harus diisi.',
                'faccount.unique' => 'Kode account sudah ada.',
                'faccount.max' => 'Kode account maksimal 10 karakter.',
                'faccname.max' => 'Nama account maksimal 50 karakter.',
                'finitjurnal.max' => 'Inisial jurnal maksimal 2 karakter.',
            ]
        );

        // Add default values for the required fields
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;  // Use the authenticated user's ID
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Set current time

        $validated['fnonactive'] = '0';

        // Handle 'fhavesubaccount' logic: set it to 1 if checked, else 0
        $validated['fhavesubaccount'] = $request->has('fhavesubaccount') ? 1 : 0;

        // Handle 'ftypesubaccount' logic if a sub-account is checked
        if ($request->has('fhavesubaccount')) {
            // Ensure subaccount type is set as a single character (S, C, P)
            $validated['ftypesubaccount'] = $request->input('ftypesubaccount') == 'Sub Account' ? 'S' : ($request->input('ftypesubaccount') == 'Customer' ? 'C' : 'P');
        } else {
            // Ensure subaccount type is disabled
            $validated['ftypesubaccount'] = '0';
        }

        // Handle fnormal field, map 'Debet' (1) and 'Kredit' (2) correctly
        $validated['fnormal'] = $request->input('fnormal');
        $validated['fend'] = $request->input('fend');

        // Handle fuserlevel, map 'User' (1), 'Supervisor' (2), and 'Admin' (3)
        $validated['fuserlevel'] = $request->input('fuserlevel');

        // Directly set 'fcurrency' to 'IDR'
        $validated['fcurrency'] = 'IDR';

        // Create the new Account record
        Account::create($validated);

        return redirect()
            ->route('account.index')
            ->with('success', 'Account berhasil ditambahkan.');
    }

    public function edit($faccid)
    {
        // Find Account by primary key
        $account = Account::findOrFail($faccid);

        return view('account.edit', compact('account'));
    }

    public function update(Request $request, $faccid)
    {
        // Validate incoming request data
        $validated = $request->validate(
            [
                'faccount' => "required|string|unique:account,faccount,{$faccid},faccid", // Exclude current account from unique check
                'faccname' => 'required|string|max:50', // Validate account name (max 50 chars)
                'fnormal' => 'required|in:1,2', // Ensure 'fnormal' is either 1 (Debet) or 2 (Kredit)
                'finitjurnal' => 'nullable|string|max:2', // Validate initial journal (max 2 chars)
                'fend' => 'required|in:1,2', // Ensure 'fend' is either 1 (Detil) or 2 (Header)
                'fuserlevel' => 'required|in:1,2,3', // Ensure 'fuserlevel' is 1 (User), 2 (Supervisor), or 3 (Admin)
            ],
            [
                'faccount.required' => 'Kode account harus diisi.',
                'faccname.required' => 'Nama account harus diisi.',
                'faccount.unique' => 'Kode account sudah ada.',
                'faccount.max' => 'Kode account maksimal 10 karakter.',
                'faccname.max' => 'Nama account maksimal 50 karakter.',
                'finitjurnal.max' => 'Inisial jurnal maksimal 2 karakter.',
            ]
        );

        // Handle the checkbox for 'fnonactive' (1 = checked, 0 = unchecked)
        $validated['fnonactive'] = '0';
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null; // Use the authenticated user's ID
        $validated['fupdatedat'] = now(); // Set current time

        $validated['fhavesubaccount'] = $request->has('fhavesubaccount') ? 1 : 0;

        // Handle 'ftypesubaccount' logic if a sub-account is checked
        if ($request->has('fhavesubaccount')) {
            // Ensure subaccount type is set as a single character (S, C, P)
            $validated['ftypesubaccount'] = $request->input('ftypesubaccount') == 'Sub Account' ? 'S' : ($request->input('ftypesubaccount') == 'Customer' ? 'C' : 'P');
        } else {
            // Ensure subaccount type is disabled
            $validated['ftypesubaccount'] = '0';
        }

        // Handle fnormal field, map 'Debet' (1) and 'Kredit' (2) correctly
        $validated['fnormal'] = $request->input('fnormal');
        $validated['fend'] = $request->input('fend');

        // Handle fuserlevel, map 'User' (1), 'Supervisor' (2), and 'Admin' (3)
        $validated['fuserlevel'] = $request->input('fuserlevel');

        // Directly set 'fcurrency' to 'IDR'
        $validated['fcurrency'] = 'IDR';

        // Find Account and update
        $account = Account::findOrFail($faccid);
        $account->update($validated);

        return redirect()
            ->route('account.index')
            ->with('success', 'Account berhasil di-update.');
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
