<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
  public function index(Request $request)
  {
    $allowedSorts = ['fcurrcode', 'fcurrname', 'frate', 'fcurrid', 'fnonactive'];
    $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fcurrid';
    $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

    $status = $request->query('status');

    $query = Currency::query();

    if ($status === 'active') {
      $query->where('fnonactive', '0');
    } elseif ($status === 'nonactive') {
      $query->where('fnonactive', '1');
    }

    $currencys = $query
      ->orderBy($sortBy, $sortDir)
      ->get(['fcurrcode', 'fcurrname', 'fcurrid', 'fnonactive']);
    $canCreate = in_array('createCurrency', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateCurrency', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteCurrency', explode(',', session('user_restricted_permissions', '')));

    return view('currency.index', compact('currencys', 'canCreate', 'canEdit', 'canDelete', 'status'));
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
    // 1. Validasi semua data yang masuk
    $validated = $request->validate(
      [
        'fcurrcode' => 'required|string|max:10|unique:mscurrency,fcurrcode',
        'fcurrname' => 'required|string|max:50|unique:mscurrency,fcurrname',
        'frate'     => 'required|numeric|min:0',
      ],
      [
        'fcurrcode.required' => 'Kode currency harus diisi.',
        'fcurrcode.unique'   => 'Kode currency sudah terdaftar.',
        'fcurrname.required' => 'Nama currency harus diisi.',
        'fcurrname.unique'   => 'Nama currency sudah terdaftar.',
        'frate.numeric'      => 'Rate harus berupa angka.',
      ]
    );

    // 2. Format data sebelum disimpan
    // Note: Jangan gunakan strtoupper pada 'frate' karena itu adalah angka (numeric)
    $validated['fcurrcode'] = strtoupper($request->fcurrcode);
    $validated['fcurrname'] = strtoupper($request->fcurrname);
    $validated['frate']     = $request->frate;
    $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

    // 3. Simpan ke database
    Currency::create($validated);

    return redirect()
      ->route('currency.index') // Biasanya redirect ke index setelah simpan
      ->with('success', 'Currency berhasil ditambahkan.');
  }

  public function edit($fcurrid)
  {
    // Find Currency by primary key
    $currency = Currency::findOrFail($fcurrid);

    return view('currency.edit', [
      'currency' => $currency,
      'action' => 'edit'
    ]);
  }

  public function view($fcurrid)
  {
    // Find Currency by primary key
    $currency = Currency::findOrFail($fcurrid);

    return view('currency.view', [
      'currency' => $currency
    ]);
  }

  public function update(Request $request, $fcurrid)
  {
    $request->merge([
      'fcurrname' => strtoupper($request->fcurrname),
      'fcurrcode' => strtoupper($request->fcurrcode),
      'frate'     => $request->frate,
    ]);

    $validated = $request->validate(
      [
        'fcurrcode' => 'required|string|max:10|unique:mscurrency,fcurrcode',
        'fcurrname' => 'required|string|string|unique:mscurrency,fcurrname',
        'frate'     => 'required|numeric|min:0',
      ],
      [
        'fcurrname.required' => 'Nama currency harus diisi.',
        'fcurrname.unique' => 'Nama currency ini sudah ada',
        'fcurrcode.unique' => 'Kode currency ini sudah ada',
        'frate.numeric' => 'Rate harus berupa angka.',
        'fcurrcode.required' => 'Kode currency harus diisi.',
        'fcurrcode.max' => 'Kode currency maksimal 10 karakter.',
      ]
    );

    $validated['fcurrname'] = strtoupper($validated['fcurrname']);

    $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

    // Find Currency and update
    $currency = Currency::findOrFail($fcurrid);
    $currency->update($validated);

    return redirect()
      ->route('currency.index')
      ->with('success', 'Currency berhasil di-update.');
  }

  public function delete($fcurrid)
  {
    $currency = Currency::findOrFail($fcurrid);
    return view('currency.edit', [
      'currency' => $currency,
      'action' => 'delete'
    ]);
  }

  public function destroy($fcurrid)
  {
    try {
      $currency = Currency::findOrFail($fcurrid);
      $currency->delete();

      return redirect()->route('currency.index')->with('success', 'Data currency ' . $currency->fcurrcode . ' berhasil dihapus.');
    } catch (\Exception $e) {
      // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
      return redirect()->route('currency.delete', $fcurrid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
  }
}
