<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrencyController extends Controller
{
    private function ensureCurrencyPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu currency.');
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureCurrencyPermission('viewCurrency')) {
            return $guard;
        }

        $currencys = Currency::orderBy('fcurrcode', 'asc')
            ->get(['fcurrcode', 'fcurrname', 'fcurrid', 'fnonactive']);

        $canCreate = in_array('createCurrency', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateCurrency', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteCurrency', explode(',', session('user_restricted_permissions', '')));

        return view('currency.index', compact('currencys', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        if ($guard = $this->ensureCurrencyPermission('createCurrency')) {
            return $guard;
        }

        return view('currency.create');
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureCurrencyPermission('createCurrency')) {
            return $guard;
        }

        $request->merge([
            'fcurrcode' => strtoupper($request->fcurrcode),
            'fcurrname' => strtoupper($request->fcurrname),
        ]);
        $validated = $request->validate(
            [
                'fcurrcode' => 'required|string|max:10|unique:mscurrency,fcurrcode',
                'fcurrname' => 'required|string|max:50|unique:mscurrency,fcurrname',
                'frate' => 'required|numeric|min:0',
            ],
            [
                'fcurrcode.required' => 'Kode currency wajib diisi.',
                'fcurrcode.unique' => 'Kode currency sudah ada.',
                'fcurrname.required' => 'Nama currency wajib diisi.',
                'fcurrname.unique' => 'Nama currency sudah ada.',
                'frate.numeric' => 'Rate harus angka.',
            ]
        );

        $validated['fcurrcode'] = strtoupper($request->fcurrcode);
        $validated['fcurrname'] = strtoupper($request->fcurrname);
        $validated['frate'] = $request->frate;
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Currency::create($validated);

        return redirect()
            ->route('currency.index')
            ->with('success', 'Currency berhasil disimpan.');
    }

    public function edit($fcurrid)
    {
        if ($guard = $this->ensureCurrencyPermission('updateCurrency')) {
            return $guard;
        }

        $currency = Currency::findOrFail($fcurrid);

        return view('currency.edit', [
            'currency' => $currency,
            'action' => 'edit',
        ]);
    }

    public function view($fcurrid)
    {
        if ($guard = $this->ensureCurrencyPermission('viewCurrency')) {
            return $guard;
        }

        $currency = Currency::findOrFail($fcurrid);

        return view('currency.view', [
            'currency' => $currency,
        ]);
    }

    public function update(Request $request, $fcurrid)
    {
        if ($guard = $this->ensureCurrencyPermission('updateCurrency')) {
            return $guard;
        }

        $request->merge([
            'fcurrname' => strtoupper($request->fcurrname),
            'fcurrcode' => strtoupper($request->fcurrcode),
            'frate' => $request->frate,
        ]);

        $validated = $request->validate(
            [
                'fcurrcode' => 'required|string|max:10|unique:mscurrency,fcurrcode,'.$fcurrid.',fcurrid',
                'fcurrname' => 'required|string|unique:mscurrency,fcurrname,'.$fcurrid.',fcurrid',
                'frate' => 'required|numeric|min:0',
            ],
            [
                'fcurrname.required' => 'Nama currency wajib diisi.',
                'fcurrname.unique' => 'Nama currency sudah ada.',
                'fcurrcode.unique' => 'Kode currency sudah ada.',
                'frate.numeric' => 'Rate harus angka.',
                'fcurrcode.required' => 'Kode currency wajib diisi.',
                'fcurrcode.max' => 'Kode currency max 10 karakter.',
            ]
        );

        $validated['fcurrname'] = strtoupper($validated['fcurrname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $currency = Currency::findOrFail($fcurrid);
        $currency->update($validated);

        return redirect()
            ->route('currency.index')
            ->with('success', 'Currency berhasil diupdate.');
    }

    public function delete($fcurrid)
    {
        if ($guard = $this->ensureCurrencyPermission('deleteCurrency')) {
            return $guard;
        }

        $currency = Currency::findOrFail($fcurrid);

        return view('currency.delete', [
            'currency' => $currency,
        ]);
    }

    public function destroy($fcurrid)
    {
        if (! $this->hasRestrictedPermission('deleteCurrency')) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu currency.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu currency.');
        }

        try {
            $currency = Currency::findOrFail($fcurrid);

            if ($this->hasUsage($currency)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency tidak bisa dihapus. Sudah direferensi.',
                ], 422);
            }

            $currency->delete();

            return response()->json([
                'success' => true,
                'message' => 'Currency '.$currency->fcurrcode.' berhasil dihapus.',
                'redirect' => route('currency.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Currency belum bisa dihapus. Coba lagi.',
            ], 500);
        }
    }

    private function hasUsage(Currency $currency): bool
    {
        $currencyId = $currency->fcurrid;
        $currencyCode = strtoupper(trim((string) $currency->fcurrcode));

        if (! empty($currencyId) && DB::table('tr_poh')->where('fcurrency', $currencyId)->exists()) {
            return true;
        }

        if ($currencyCode === '') {
            return false;
        }

        $codeReferencedTables = [
            'trstockmt',
            'trsomt',
            'tranmt',
            'mscustomer',
            'account',
        ];

        foreach ($codeReferencedTables as $table) {
            if (DB::table($table)->where('fcurrency', $currencyCode)->exists()) {
                return true;
            }
        }

        return false;
    }
}
