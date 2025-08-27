<?php

namespace App\Http\Controllers;

use App\Models\Tr_prh;
use App\Models\Tr_prd;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Tr_prhController extends Controller
{
  public function index(Request $request)
  {
    $filterBy = in_array($request->filter_by, ['fprno', 'fprdin'])
      ? $request->filter_by
      : 'fprno';

    $search = $request->search;
    $tr_prh = Tr_prh::when($search, function ($q) use ($filterBy, $search) {
      $q->where($filterBy, 'ILIKE', '%' . $search . '%');
    })
      ->orderBy('fprid', 'desc')
      ->paginate(10)
      ->withQueryString();

    return view('tr_prh.index', compact('tr_prh', 'filterBy', 'search'));
  }

  private function generatetr_prh_Code(): string
  {
    $lastCode = Tr_prh::where('fprno', 'like', 'C-%')
      ->orderByRaw("CAST(SUBSTRING(fprno FROM 3) AS INTEGER) DESC")
      ->value('fprno');

    if (!$lastCode) {
      return 'C-01';
    }

    $number = (int)substr($lastCode, 2);
    $newNumber = $number + 1;

    return 'C-' . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
  }

  public function create()
  {
    $supplier        = Supplier::all();
    $fcabang         = Auth::user()->fcabang ?? null;
    $newtr_prh_code  = $this->generatetr_prh_Code();

    $products = Product::select(
      'fproductid',
      'fproductcode',
      'fproductname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fproductname')
      ->get();

    return view('tr_prh.create', compact('newtr_prh_code', 'supplier', 'fcabang', 'products'));
  }

  public function store(Request $request)
  {
    $request->validate([
      'fprdate'   => ['required', 'date'],
      'fsupplier' => ['required', 'string', 'max:10'],
      'fneeddate' => ['nullable', 'date'],
      'fduedate'  => ['nullable', 'date'],
      'fket'      => ['nullable', 'string', 'max:300'],
      'fbranchcode' => ['nullable', 'string', 'max:20'],
      'fitemcode'   => ['array'],
      'fitemcode.*' => ['nullable', 'string', 'max:50'],
      'fsatuan'     => ['array'],
      'fsatuan.*'   => ['nullable', 'string', 'max:20'],
      'fqty'        => '',
      'fqty.*'      => '',
      'fdesc'       => ['array'],
      'fdesc.*'     => ['nullable', 'string'],
      'fketdt'      => ['array'],
      'fketdt.*'    => ['nullable', 'string', 'max:50'],
    ], [
      'fprdate.required'   => 'Tanggal PR wajib diisi.',
      'fsupplier.required' => 'Supplier wajib dipilih.',
    ]);

    $fprno = $request->filled('fprno') ? $request->fprno : $this->generatetr_prh_Code();

    $fprdate   = Carbon::parse($request->fprdate)->startOfDay();
    $fneeddate = $request->filled('fneeddate') ? Carbon::parse($request->fneeddate)->startOfDay() : null;
    $fduedate  = $request->filled('fduedate')  ? Carbon::parse($request->fduedate)->startOfDay()  : null;

    $authUser    = auth('sysuser')->user();
    $userName    = $authUser->fname ?? null;

    $codes   = $request->input('fitemcode', []);
    $sats    = $request->input('fsatuan', []);
    $qtys  = $request->input('fqty', []);
    $descs   = $request->input('fdesc', []);
    $ketdts  = $request->input('fketdt', []);

    $validator = Validator::make([], []);
    $stocks = Product::whereIn('fproductcode', $codes)->pluck('fminstock', 'fproductcode');

    foreach ($codes as $i => $code) {
      $code = trim($code ?? '');
      if ($code === '') continue;

      $max = (int)($stocks[$code] ?? 0);
      $qty = (int)($qtys[$i] ?? 0);

      if ($max > 0 && $qty > $max) {
        $validator->errors()->add("fqty.$i", "Qty untuk produk $code tidak boleh melebihi stok ($max).");
      }
      if ($qty < 1) {
        $validator->errors()->add("fqty.$i", "Qty minimal 1.");
      }
    }

    if ($validator->fails()) {
      return back()->withErrors($validator)->withInput();
    }

    $detailRows = [];
    $now = now();

    $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts));

    for ($i = 0; $i < $rowCount; $i++) {
      $code  = trim($codes[$i]  ?? '');
      $sat   = trim($sats[$i]   ?? '');
      $qty   = $qtys[$i]        ?? null;
      $desc  = $descs[$i]       ?? null;
      $ketdt = $ketdts[$i]      ?? null;

      if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty >= 1) {
        $detailRows[] = [
          'fprnoid'    => $fprno,
          'fprdcode'   => $code,
          'fqty'       => (int)$qty,
          'fqtyremain' => (int)$qty,
          'fprice'     => 0,
          'fketdt'     => $ketdt,
          'fcreatedat' => $now,
          'fsatuan'    => $sat,
          'fdesc'      => $desc,
          'fuserid'    => $userName,
        ];
      }
    }

    if (empty($detailRows)) {
      return back()->withInput()
        ->withErrors(['detail' => 'Minimal satu item detail dengan Kode, Satuan, dan Qty ≥ 1.']);
    }

    DB::transaction(function () use ($request, $fprno, $fprdate, $fneeddate, $fduedate, $userName, $detailRows, $codes, $qtys) {
      Tr_prh::create([
        'fprno'         => $fprno,
        'fprdate'       => $fprdate,
        'fsupplier'     => $request->fsupplier,
        'fprdin'        => '0',
        'fclose'        => '0',
        'fket'          => $request->fket,
        'fbranchcode'   => $request->fbranchcode,
        'fcreatedat'    => now(),
        'fneeddate'     => $fneeddate,
        'fduedate'      => $fduedate,
        'fuserid'       => $userName,
        'fuserapproved' => $request->has('fuserapproved') ? $userName : null,
        'fdateapproved' => $request->has('fuserapproved') ? now() : null,
        'fupdatedat'    => null,
      ]);

      Tr_prd::insert($detailRows);

      foreach ($codes as $i => $code) {
        $qty = (int)($qtys[$i] ?? 0);
        if ($qty > 0) {
          DB::table('msproduct')
            ->where('fproductcode', $code)
            ->update([
              'fminstock' => DB::raw("CAST(fminstock AS INTEGER) - $qty"),
              'fupdatedat' => now(),
            ]);
        }
      }
    });

    return redirect()->route('tr_prh.index')
      ->with('success', 'Permintaan pembelian berhasil ditambahkan.');
  }

  public function edit($fprid)
  {
    // Fetch the PR data
    $tr_prh = Tr_prh::findOrFail($fprid);
    $supplier = Supplier::all();
    $fcabang = Auth::user()->fcabang ?? null;

    // Get all products for selection in the form
    $products = Product::select(
      'fproductid',
      'fproductcode',
      'fproductname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fproductname')->get();

    // Map the products to be used in the frontend
    $productMap = $products->mapWithKeys(function ($product) {
      // Collect the units dynamically
      $units = array_filter([
        $product->fsatuankecil,
        $product->fsatuanbesar,
        $product->fsatuanbesar2
      ]);

      return [
        $product->fproductcode => [
          'name' => $product->fproductname,
          'units' => array_values($units),  // Ensure this is an array
          'stock' => $product->fminstock
        ]
      ];
    });

    // Ensure that the dates are Carbon instances
    $tr_prh->fprdate = Carbon::parse($tr_prh->fprdate)->toDateString();
    $tr_prh->fneeddate = $tr_prh->fneeddate ? Carbon::parse($tr_prh->fneeddate)->toDateString() : null;
    $tr_prh->fduedate = $tr_prh->fduedate ? Carbon::parse($tr_prh->fduedate)->toDateString() : null;

    // Add the product units and existing fsatuan to each detail
    foreach ($tr_prh->details as $detail) {
      $detail->units = $productMap[$detail->fprdcode]['units'] ?? [];
      $detail->fsatuan = $detail->fsatuan ?? $productMap[$detail->fprdcode]['units'][0]; // Set default unit if missing
    }

    return view('tr_prh.edit', compact('tr_prh', 'supplier', 'fcabang', 'products', 'productMap'));
  }

  public function update(Request $request, $fprid)
  {
    // Log the incoming request data
    Log::debug('Update Request Data:', $request->all());

    // Validate incoming data
    $validator = Validator::make($request->all(), [
      'fprdate' => ['required', 'date'],
      'fsupplier' => ['required', 'string', 'max:10'],
      'fneeddate' => ['nullable', 'date'],
      'fduedate' => ['nullable', 'date'],
      'fket' => ['nullable', 'string', 'max:300'],
      'fbranchcode' => ['nullable', 'string', 'max:20'],
      'fitemcode' => ['array'],
      'fitemcode.*' => ['nullable', 'string', 'max:50'],
      'fsatuan' => ['array'],
      'fsatuan.*' => ['nullable', 'string', 'max:20'],
      'fqty' => 'array',
      'fqty.*' => 'nullable|integer|min:1',
      'fdesc' => ['array'],
      'fdesc.*' => ['nullable', 'string'],
      'fketdt' => ['array'],
      'fketdt.*' => ['nullable', 'string', 'max:50'],
    ]);

    // If validation fails, log errors and return to previous page
    if ($validator->fails()) {
      Log::debug('Validation errors:', $validator->errors()->all());
      return back()->withErrors($validator)->withInput();
    }

    // Get form data
    $fprno = $request->filled('fprno') ? $request->fprno : $this->generatetr_prh_Code();
    $fprdate = Carbon::parse($request->fprdate)->startOfDay();
    $fneeddate = $request->filled('fneeddate') ? Carbon::parse($request->fneeddate)->startOfDay() : null;
    $fduedate = $request->filled('fduedate') ? Carbon::parse($request->fduedate)->startOfDay() : null;
    $userName = Auth::user()->fname;

    Log::debug('Updating Tr_prh with SQL:', [
      'fprno' => $fprno,
      'fprdate' => $fprdate,
      'fsupplier' => $request->fsupplier,
      'fket' => $request->fket,
      'fbranchcode' => $request->fbranchcode,
    ]);

    DB::enableQueryLog(); // Enable query log

    $codes = array_filter($request->input('fitemcode', []), function ($value) {
      return !is_null($value);
    });
    $sats = array_filter($request->input('fsatuan', []), function ($value) {
      return !is_null($value);
    });
    $qtys = array_filter($request->input('fqty', []), function ($value) {
      return !is_null($value);
    });
    $descs = array_filter($request->input('fdesc', []), function ($value) {
      return !is_null($value);
    });
    $ketdts = array_filter($request->input('fketdt', []), function ($value) {
      return !is_null($value);
    });

    // Validate quantities against stock
    $stocks = Product::whereIn('fproductcode', $codes)->pluck('fminstock', 'fproductcode');
    $validator = Validator::make([], []);

    foreach ($codes as $i => $code) {
      $max = (int)($stocks[$code] ?? 0);
      $qty = (int)($qtys[$i] ?? 0);

      if ($max > 0 && $qty > $max) {
        $validator->errors()->add("fqty.$i", "Qty untuk produk $code tidak boleh melebihi stok ($max).");
      }
      if ($qty < 1) {
        $validator->errors()->add("fqty.$i", "Qty minimal 1.");
      }
    }

    if ($validator->fails()) {
      Log::debug('Validation errors after quantity check:', $validator->errors()->all());
      return back()->withErrors($validator)->withInput();
    }

    // Log the detailRows before updating Tr_prd
    $detailRows = [];
    $now = now();
    $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts));

    for ($i = 0; $i < $rowCount; $i++) {
      $code = trim($codes[$i] ?? '');
      $sat = trim($sats[$i] ?? '');
      $qty = $qtys[$i] ?? null;
      $desc = $descs[$i] ?? null;
      $ketdt = $ketdts[$i] ?? null;

      if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty >= 1) {
        $detailRows[] = [
          'fprnoid' => $fprno,
          'fprdcode' => $code,
          'fqty' => (int)$qty,
          'fqtyremain' => (int)$qty,
          'fprice' => 0,
          'fketdt' => $ketdt,
          'fcreatedat' => $now,
          'fsatuan' => $sat,
          'fdesc' => $desc,
          'fuserid' => $userName,
        ];
      }
    }

    // Log the detailRows array
    Log::debug('Detail rows to be inserted:', $detailRows);

    // Ensure that there is at least one detail row
    if (empty($detailRows)) {
      Log::debug('No detail rows found.');
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item detail dengan Kode, Satuan, dan Qty ≥ 1.']);
    }

    DB::transaction(function () use ($request, $fprno, $fprdate, $fneeddate, $fduedate, $userName, $detailRows, $codes, $qtys) {
      // Update Tr_prh header
      Tr_prh::where('fprno', $fprno)->update([
        'fprdate' => $fprdate,
        'fsupplier' => $request->fsupplier,
        'fprdin' => '0',
        'fclose' => $request->has('fclose') ? '1' : '0',
        'fket' => $request->fket,
        'fbranchcode' => $request->fbranchcode,
        'fupdatedat' => now(),
        'fneeddate' => $fneeddate,
        'fduedate' => $fduedate,
        'fuserid' => $userName,
      ]);

      Log::debug('Tr_prh update executed successfully.');

      // Update Tr_prd details
      foreach ($detailRows as $row) {
        Log::debug('Updating Tr_prd detail:', $row);
        Tr_prd::where('fprnoid', $fprno)
          ->where('fprdcode', $row['fprdcode'])
          ->update([
            'fqty' => $row['fqty'],
            'fqtyremain' => $row['fqtyremain'],
            'fsatuan' => $row['fsatuan'],
            'fdesc' => $row['fdesc'],
            'fketdt' => $row['fketdt'],
            'fupdatedat' => now(),
          ]);
      }

      // Update product stock
      foreach ($codes as $i => $code) {
        $qty = (int)($qtys[$i] ?? 0);
        if ($qty > 0) {
          Log::debug('Updating product stock for code:', ['code' => $code]);
          DB::table('msproduct')
            ->where('fproductcode', $code)
            ->update([
              'fminstock' => DB::raw("CAST(fminstock AS INTEGER) - $qty"),
              'fupdatedat' => now(),
            ]);
        }
      }
    });

    return redirect()->route('tr_prh.index')
      ->with('success', 'Permintaan pembelian berhasil diperbarui.');
  }
  
  public function destroy($fsatuanid)
  {
    $satuan = Tr_prh::findOrFail($fsatuanid);
    $satuan->delete();

    return redirect()
      ->route('tr_prh.index')
      ->with('success', 'Satuan berhasil dihapus.');
  }
}
