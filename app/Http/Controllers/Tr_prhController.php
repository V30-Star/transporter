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

  private function generatetr_prh_Code(?Carbon $onDate = null, $branch = null): string
  {
    $date = $onDate ?: now();

    $branch = $branch
      ?? Auth::guard('sysuser')->user()?->fcabang
      ?? Auth::user()?->fcabang
      ?? null;

    $kodeCabang = null;

    if ($branch !== null) {
      $needle = trim((string)$branch);

      if (is_numeric($needle)) {
        $kodeCabang = DB::table('mscabang')
          ->where('fcabangid', (int)$needle)
          ->value('fcabangkode');
      } else {
        // cocokkan case-insensitive
        $kodeCabang = DB::table('mscabang')
          ->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$needle])
          ->value('fcabangkode');

        if (!$kodeCabang) {
          $kodeCabang = DB::table('mscabang')
            ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
            ->value('fcabangkode');
        }
      }
    }

    if (!$kodeCabang) {
      $kodeCabang = 'NA'; // fallback
    }

    $prefix = sprintf('PR.%s.%s.%s.', trim($kodeCabang), $date->format('y'), $date->format('m'));

    return DB::transaction(function () use ($prefix) {
      $last = \App\Models\Tr_prh::where('fprno', 'like', $prefix . '%')
        ->lockForUpdate()
        ->orderByDesc('fprno')
        ->first();

      $lastNum = 0;
      if ($last && ($pos = strrpos($last->fprno, '.')) !== false) {
        $lastNum = (int)substr($last->fprno, $pos + 1);
      }

      $next = str_pad((string)($lastNum + 1), 4, '0', STR_PAD_LEFT);
      return $prefix . $next; // PR.JK.25.08.0001
    });
  }

  public function print(string $fprno)
  {
    // subquery aman mengikuti $table dari model Supplier
    $supplierSub = Supplier::select('fsuppliercode', 'fsuppliername');

    $hdr = Tr_prh::query()
      ->leftJoinSub($supplierSub, 's', function ($join) {
        $join->on('s.fsuppliercode', '=', 'tr_prh.fsupplier'); // kalau menyimpan KODE
        // jika yang disimpan adalah ID, ganti ke:
        // $join->on('s.fsupplierid', '=', 'tr_prh.fsupplier');
      })
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_prh.fbranchcode')
      ->where('tr_prh.fprno', $fprno)
      ->first([
        'tr_prh.*',
        's.fsuppliername as supplier_name',
        'c.fcabangname as cabang_name',
      ]);

    abort_if(!$hdr, 404);

    $dt = Tr_prd::query()
      ->leftJoin('msproduct as p', 'p.fproductcode', '=', 'tr_prd.fprdcode')
      ->where('tr_prd.fprnoid', $hdr->fprno)
      ->orderBy('tr_prd.fprdcode')
      ->get([
        'tr_prd.*',
        'p.fproductname as product_name',
        'p.fminstock as stock',
      ]);

    $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '-';

    return view('tr_prh.print', [
      'hdr' => $hdr,
      'dt'  => $dt,
      'fmt' => $fmt,
      'company_name' => config('app.company_name', 'PT.DEMO VERSION'),
      'company_city' => config('app.company_city', 'Tangerang'),
    ]);
  }

  public function create()
  {
    $supplier        = Supplier::all();

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int)$raw))
      ->when(
        !is_numeric($raw),
        fn($q) => $q
          ->where('fcabangkode', $raw)
          ->orWhere('fcabangname', $raw)
      )
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $fcabang       = $branch->fcabangname ?? (string)$raw;   // untuk tampilan
    $fbranchcode   = $branch->fcabangkode ?? (string)$raw;   // untuk hidden post (JK)

    $newtr_prh_code = $this->generatetr_prh_Code(now(), $fbranchcode);

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

    return view('tr_prh.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'supplier'       => $supplier,
      'fcabang'        => $fcabang,
      'fbranchcode'    => $fbranchcode,
      'products'       => $products,
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'fprdate'   => ['nullable', 'date'],
      'fsupplier' => ['nullable', 'string', 'max:10'],
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
    ]);

    $fprdate = Carbon::parse($request->fprdate)->startOfDay();

    // biarkan apa adanya (bisa "JK", "Jakarta", atau angka id)
    $branchFromForm = $request->input('fbranchcode');  // no cast

    $fprno = $request->filled('fprno')
      ? $request->fprno
      : $this->generatetr_prh_Code($fprdate, $branchFromForm);

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

    // ====== 2) TAMBAHKAN validasi berbasis data tabel (hidden inputs row_*) ======
    $rowPrdate   = $request->input('row_prdate', []);
    $rowSupplier = $request->input('row_supplier', []);

    // helper ambil pertama yang tidak kosong
    $firstNonEmpty = function (array $arr) {
      foreach ($arr as $v) {
        $t = trim((string)($v ?? ''));
        if ($t !== '') return $t;
      }
      return '';
    };

    $hdrPrdate   = $firstNonEmpty($rowPrdate);
    $hdrSupplier = $firstNonEmpty($rowSupplier);

    // jika di tabel kosong, lempar error sesuai pesan lama
    if ($hdrPrdate === '') {
      $validator->errors()->add('fprdate', 'Tanggal PR wajib diisi.');
    }
    if ($hdrSupplier === '') {
      $validator->errors()->add('fsupplier', 'Supplier wajib dipilih.');
    }

    // (opsional) konsistensi agar semua baris tabel punya header yang sama
    $uniq = fn(array $arr) => collect($arr)->filter(fn($v) => trim((string)$v) !== '')->unique()->values();
    if ($uniq($rowSupplier)->count() > 1) {
      $validator->errors()->add('fsupplier', 'Supplier pada baris-baris tabel harus sama.');
    }
    if ($uniq($rowPrdate)->count() > 1) {
      $validator->errors()->add('fprdate', 'Tanggal PR pada baris-baris tabel harus sama.');
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
    $supplier = Supplier::all();

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q
        ->where('fcabangkode', $raw)
        ->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    // >>> MUAT HEADER + DETAIL BERDASARKAN fprid
    $tr_prh = Tr_prh::with(['details' => function ($q) {
      $q->orderBy('fprdcode');
    }])->where('fprid', $fprid)->firstOrFail();

    // Data produk untuk dropdown & map satuan
    $products = Product::select(
      'fproductid',
      'fproductcode',
      'fproductname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fproductname')->get();

    // (opsional) productMap server-side jika ingin dipakai di Blade
    $productMap = $products->mapWithKeys(function ($p) {
      return [
        $p->fproductcode => [
          'name'  => $p->fproductname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    return view('tr_prh.edit', [
      'supplier'     => $supplier,
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap, // jika dipakai di Blade
      'tr_prh'       => $tr_prh,     // <<— PENTING
    ]);
  }

  public function update(Request $request, $fprid)
  {
    // Ambil header berdasar fprid
    $header = Tr_prh::where('fprid', $fprid)->firstOrFail();
    $fprno  = $header->fprno; // dipakai untuk detail (kolom fprnoid)

    // Validasi header & detail
    $validator = Validator::make($request->all(), [
      'fprdate'    => ['required', 'date'],
      'fsupplier'  => ['required', 'string', 'max:10'],
      'fneeddate'  => ['nullable', 'date'],
      'fduedate'   => ['nullable', 'date'],
      'fket'       => ['nullable', 'string', 'max:300'],
      'fbranchcode' => ['nullable', 'string', 'max:20'],
      'fitemcode'  => ['array'],
      'fitemcode.*' => ['nullable', 'string', 'max:50'],
      'fsatuan'    => ['array'],
      'fsatuan.*'  => ['nullable', 'string', 'max:20'],
      'fqty'       => ['array'],
      'fqty.*'     => ['nullable', 'integer', 'min:1'],
      'fdesc'      => ['array'],
      'fdesc.*'    => ['nullable', 'string'],
      'fketdt'     => ['array'],
      'fketdt.*'   => ['nullable', 'string', 'max:50'],
    ]);

    if ($validator->fails()) {
      Log::debug('Validation errors:', $validator->errors()->all());
      return back()->withErrors($validator)->withInput();
    }

    // Parse tanggal
    $fprdate   = Carbon::parse($request->fprdate)->startOfDay();
    $fneeddate = $request->filled('fneeddate') ? Carbon::parse($request->fneeddate)->startOfDay() : null;
    $fduedate  = $request->filled('fduedate') ? Carbon::parse($request->fduedate)->startOfDay() : null;
    $userName  = Auth::user()->fname ?? 'system';

    // Ambil arrays detail
    $codes  = array_values(array_filter($request->input('fitemcode', []), fn($v) => !is_null($v)));
    $sats   = array_values(array_filter($request->input('fsatuan', []), fn($v) => !is_null($v)));
    $qtys   = array_values(array_filter($request->input('fqty', []), fn($v) => !is_null($v)));
    $descs  = array_values(array_filter($request->input('fdesc', []), fn($v) => !is_null($v)));
    $ketdts = array_values(array_filter($request->input('fketdt', []), fn($v) => !is_null($v)));

    // Cek stok vs qty
    $stocks = Product::whereIn('fproductcode', $codes)->pluck('fminstock', 'fproductcode');
    $extraValidator = Validator::make([], []);
    foreach ($codes as $i => $code) {
      $max = (int)($stocks[$code] ?? 0);
      $qty = (int)($qtys[$i] ?? 0);
      if ($max > 0 && $qty > $max) {
        $extraValidator->errors()->add("fqty.$i", "Qty untuk produk $code tidak boleh melebihi stok ($max).");
      }
      if ($qty < 1) {
        $extraValidator->errors()->add("fqty.$i", "Qty minimal 1.");
      }
    }
    if ($extraValidator->fails()) {
      Log::debug('Validation errors after quantity check:', $extraValidator->errors()->all());
      return back()->withErrors($extraValidator)->withInput();
    }

    // Susun detail rows yang valid
    $detailRows = [];
    $now = now();
    $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts));
    for ($i = 0; $i < $rowCount; $i++) {
      $code = trim($codes[$i] ?? '');
      $sat  = trim($sats[$i] ?? '');
      $qty  = $qtys[$i] ?? null;
      $desc = $descs[$i] ?? null;
      $ket  = $ketdts[$i] ?? null;

      if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty >= 1) {
        $detailRows[] = [
          'fprnoid'    => $fprno,
          'fprdcode'   => $code,
          'fqty'       => (int)$qty,
          'fqtyremain' => (int)$qty,
          'fprice'     => 0,
          'fketdt'     => $ket,
          'fcreatedat' => $now,
          'fsatuan'    => $sat,
          'fdesc'      => $desc,
          'fuserid'    => $userName,
        ];
      }
    }
    if (empty($detailRows)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item detail dengan Kode, Satuan, dan Qty ≥ 1.']);
    }

    // Eksekusi update
    DB::transaction(function () use ($request, $header, $fprno, $fprdate, $fneeddate, $fduedate, $userName, $detailRows, $codes, $qtys) {
      // Update header by fprid (lebih aman sesuai route)
      Tr_prh::where('fprid', $header->fprid)->update([
        'fprdate'     => $fprdate,
        'fsupplier'   => $request->fsupplier,
        'fprdin'      => '0',
        'fclose'      => $request->has('fclose') ? '1' : '0',
        'fket'        => $request->fket,
        'fbranchcode' => $request->fbranchcode,
        'fupdatedat'  => now(),
        'fneeddate'   => $fneeddate,
        'fduedate'    => $fduedate,
        'fuserid'     => $userName,
      ]);

      // Update detail baris-per-baris (jika baris belum ada, kamu bisa ubah ke upsert)
      foreach ($detailRows as $row) {
        Tr_prd::where('fprnoid', $fprno)
          ->where('fprdcode', $row['fprdcode'])
          ->update([
            'fqty'       => $row['fqty'],
            'fqtyremain' => $row['fqtyremain'],
            'fsatuan'    => $row['fsatuan'],
            'fdesc'      => $row['fdesc'],
            'fketdt'     => $row['fketdt'],
            'fupdatedat' => now(),
          ]);
      }

      // Update stok produk
      foreach ($codes as $i => $code) {
        $qty = (int)($qtys[$i] ?? 0);
        if ($qty > 0) {
          DB::table('msproduct')
            ->where('fproductcode', $code)
            ->update([
              'fminstock'  => DB::raw("CAST(fminstock AS INTEGER) - $qty"),
              'fupdatedat' => now(),
            ]);
        }
      }
    });

    return redirect()->route('tr_prh.index')->with('success', 'Permintaan pembelian berhasil diperbarui.');
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
