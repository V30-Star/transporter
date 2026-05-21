<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $currencys = Currency::orderBy('fcurrcode', 'asc')
            ->get(['fcurrcode', 'fcurrname', 'fcurrid', 'fnonactive']);

        $canCreate = in_array('createCurrency', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateCurrency', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteCurrency', explode(',', session('user_restricted_permissions', '')));

        return view('currency.index', compact('currencys', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        return view('currency.create');
    }

    public function store(Request $request)
    {
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
                'fcurrcode.required' => 'KODE CURRENCY WAJIB DIISI.',
                'fcurrcode.unique' => 'KODE CURRENCY SUDAH ADA.',
                'fcurrname.required' => 'NAMA CURRENCY WAJIB DIISI.',
                'fcurrname.unique' => 'NAMA CURRENCY SUDAH ADA.',
                'frate.numeric' => 'RATE HARUS ANGKA.',
            ]
        );

        $validated['fcurrcode'] = strtoupper($request->fcurrcode);
        $validated['fcurrname'] = strtoupper($request->fcurrname);
        $validated['frate'] = $request->frate;
        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        Currency::create($validated);

        return redirect()
            ->route('currency.index')
            ->with('success', 'Currency berhasil ditambahkan.');
    }

    public function edit($fcurrid)
    {
        $currency = Currency::findOrFail($fcurrid);

        return view('currency.edit', [
            'currency' => $currency,
            'action' => 'edit',
        ]);
    }

    public function view($fcurrid)
    {
        $currency = Currency::findOrFail($fcurrid);

        return view('currency.view', [
            'currency' => $currency,
        ]);
    }

    public function update(Request $request, $fcurrid)
    {
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
                'fcurrname.required' => 'NAMA CURRENCY WAJIB DIISI.',
                'fcurrname.unique' => 'NAMA CURRENCY SUDAH ADA.',
                'fcurrcode.unique' => 'KODE CURRENCY SUDAH ADA.',
                'frate.numeric' => 'RATE HARUS ANGKA.',
                'fcurrcode.required' => 'KODE CURRENCY WAJIB DIISI.',
                'fcurrcode.max' => 'KODE CURRENCY MAX 10 KARAKTER.',
            ]
        );

        $validated['fcurrname'] = strtoupper($validated['fcurrname']);

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        $currency = Currency::findOrFail($fcurrid);
        $currency->update($validated);

        return redirect()
            ->route('currency.index')
            ->with('success', 'Currency berhasil di-update.');
    }

    public function delete($fcurrid)
    {
        $currency = Currency::findOrFail($fcurrid);

        return view('currency.delete', [
            'currency' => $currency,
        ]);
    }

    public function destroy($fcurrid)
    {
        try {
            $currency = Currency::findOrFail($fcurrid);

            if (\Illuminate\Support\Facades\DB::table('mscurrency')->where('fcurrid', $currency->fcurrid)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency sudah digunakan dalam detail currency.',
                ], 422);
            }

            $currency->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data currency '.$currency->fcurrcode.' berhasil dihapus.',
                'redirect' => route('currency.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data belum berhasil dihapus. Silakan coba lagi.',
            ], 500);
        }
    }
}
