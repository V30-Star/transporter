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
use App\Mail\ApprovalEmail;
use Illuminate\Support\Facades\Mail;


class Tr_pohController extends Controller
{
  public function index(Request $request)
  {
    $search   = trim((string) $request->search);
    $filterBy = $request->filter_by ?? 'all'; // all | fprno | fprdin

    $tr_poh = Tr_prh::when($search !== '', function ($q) use ($search, $filterBy) {
      $q->where(function ($qq) use ($search, $filterBy) {
        if ($filterBy === 'fprno') {
          $qq->where('fprno', 'ILIKE', "%{$search}%");
        } elseif ($filterBy === 'fprdin') {
          $qq->where('fprdin', 'ILIKE', "%{$search}%");
        } else { // all
          $qq->where('fprno',  'ILIKE', "%{$search}%")
            ->orWhere('fprdin', 'ILIKE', "%{$search}%");
        }
      });
    })
      ->orderBy('fprid', 'desc')
      ->paginate(10)
      ->withQueryString();

    // permissions (ganti nama sesuai yang dipakai di app kamu)
    $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));

    // Response AJAX
    if ($request->ajax()) {
      $rows = collect($tr_poh->items())->map(function ($t) {
        return [
          'fprid'  => $t->fprid,
          'fprno'  => $t->fprno,
          'fprdin' => $t->fprdin,
          'edit_url'    => route('tr_poh.edit', $t->fprid),
          'destroy_url' => route('tr_poh.destroy', $t->fprid),
          'print_url'   => route('tr_poh.print', $t->fprno), // ← tambah ini
        ];
      });

      return response()->json([
        'data'  => $rows,
        'perms' => ['can_create' => true, 'can_edit' => true, 'can_delete' => true],
        'links' => [
          'prev'         => $tr_poh->previousPageUrl(),
          'next'         => $tr_poh->nextPageUrl(),
          'current_page' => $tr_poh->currentPage(),
          'last_page'    => $tr_poh->lastPage(),
        ],
      ]);
    }

    // Render awal
    return view('tr_poh.index', compact('tr_poh', 'filterBy', 'search', 'canCreate', 'canEdit', 'canDelete'));
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
        $join->on('s.fsuppliercode', '=', 'tr_poh.fsupplier');
      })
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_poh.fbranchcode')
      ->where('tr_poh.fprno', $fprno)
      ->first([
        'tr_poh.*',
        's.fsuppliername as supplier_name',
        'c.fcabangname as cabang_name',
      ]);

    abort_if(!$hdr, 404);

    $dt = Tr_prd::query()
      ->leftJoin('msprd as p', 'p.fproductcode', '=', 'tr_prd.fprdcode')
      ->where('tr_prd.fprnoid', $hdr->fprno)
      ->orderBy('tr_prd.fprdcode')
      ->get([
        'tr_prd.*',
        'p.fproductname as product_name',
        'p.fminstock as stock',
      ]);

    $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '-';

    return view('tr_poh.print', [
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

    $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));

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

    return view('tr_poh.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'perms' => ['can_approval' => $canApproval],
      'supplier'       => $supplier,
      'fcabang'        => $fcabang,
      'fbranchcode'    => $fbranchcode,
      'products'       => $products,
    ]);
  }

  public function store(Request $request)
  {
    // Validasi request
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
      'fapproval' => ['nullable'],
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

    // Proses tanggal PR
    $fprdate = Carbon::parse($request->fprdate)->startOfDay();
    $branchFromForm = $request->input('fbranchcode');  // no cast

    // Jika tidak ada kode PR, buat kode baru
    $fprno = $request->filled('fprno')
      ? $request->fprno
      : $this->generatetr_prh_Code($fprdate, $branchFromForm);

    // Proses tanggal lainnya
    $fneeddate = $request->filled('fneeddate') ? Carbon::parse($request->fneeddate)->startOfDay() : null;
    $fduedate  = $request->filled('fduedate')  ? Carbon::parse($request->fduedate)->startOfDay()  : null;

    $authUser    = auth('sysuser')->user();
    $userName    = $authUser->fname ?? null;

    // Ambil data item
    $codes   = $request->input('fitemcode', []);
    $sats    = $request->input('fsatuan', []);
    $qtys  = $request->input('fqty', []);
    $descs   = $request->input('fdesc', []);
    $ketdts  = $request->input('fketdt', []);

    // Cek stok produk
    $stocks = Product::whereIn('fproductcode', $codes)->pluck('fminstock', 'fproductcode');

    $validator = Validator::make([], []);
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

    // Membuat detail rows
    $detailRows = [];
    $now = now();

    $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts));

    for ($i = 0; $i < $rowCount; $i++) {
      $code  = trim($codes[$i] ?? '');
      $sat   = trim($sats[$i] ?? '');
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

    // Menyimpan data header dan detail dalam transaksi
    DB::transaction(function () use ($request, $fprno, $fprdate, $fneeddate, $fduedate, $userName, $detailRows, $codes, $qtys) {
      // Menyimpan data header
      $isApproval = (int)($request->input('fapproval', 0)); // 1 jika dicentang, 0 jika tidak
      $tr_poh = Tr_prh::create([
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
        'fapproval'     => $isApproval,
      ]);

      // Menyimpan detail barang
      Tr_prd::insert($detailRows);

      // Update stok produk
      foreach ($codes as $i => $code) {
        $qty = (int)($qtys[$i] ?? 0);
        if ($qty > 0) {
          DB::table('msprd')
            ->where('fproductcode', $code)
            ->update([
              'fminstock' => DB::raw("CAST(fminstock AS INTEGER) - $qty"),
              'fupdatedat' => now(),
            ]);
        }
      }

      if ($isApproval === 1) {
        $hdr = Tr_prh::where('fprno', $fprno)->first();
        $dt = Tr_prd::query()
          ->leftJoin('msprd as p', 'p.fproductcode', '=', 'tr_prd.fprdcode')
          ->where('tr_prd.fprnoid', $hdr->fprno)
          ->orderBy('tr_prd.fprdcode')
          ->get([
            'tr_prd.*',
            'p.fproductname as product_name',
            'p.fminstock as stock',
          ]);

        $productName = $dt->pluck('fprdcode')->implode(', ');
        $approver = auth('sysuser')->user()->fname;

        Mail::to('vierybiliam8@gmail.com')->send(new ApprovalEmail($hdr, $dt, $productName, $approver));
      }
    });

    return redirect()->route('tr_poh.index')
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
    $tr_poh = Tr_prh::with(['details' => function ($q) {
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

    return view('tr_poh.edit', [
      'supplier'     => $supplier,
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap, // jika dipakai di Blade
      'tr_poh'       => $tr_poh,     // <<— PENTING
    ]);
  }

  public function update(Request $request, $fprid)
  {
    // Ambil header berdasar fprid
    $header = Tr_prh::where('fprid', $fprid)->firstOrFail();
    $fprno  = $header->fprno; // dipakai untuk detail (kolom fprnoid)

    // Validasi header & detail
    $validator = Validator::make($request->all(), [
      'fprdate'     => ['nullable', 'date'],   // was required; make nullable so we can keep old when blank
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
      'fapproval' => ['nullable', 'boolean'],
    ]);

    // Parse tanggal
    $fprdate   = $request->filled('fprdate')
      ? Carbon::parse($request->fprdate)->startOfDay()
      : $header->fprdate;
    $fneeddate = $request->has('fneeddate') && $request->fneeddate !== ''
      ? Carbon::parse($request->fneeddate)->startOfDay()
      : $header->fneeddate;

    $fduedate  = $request->has('fduedate') && $request->fduedate !== ''
      ? Carbon::parse($request->fduedate)->startOfDay()
      : $header->fduedate;

    $userName  = Auth::user()->fname ?? 'system';

    $actor        = auth('sysuser')->user()->fname ?? (Auth::user()->fname ?? 'system');
    $approveNow   = $request->boolean('fapproval');               // 0/1 -> bool
    $wasApproved  = !empty($header->fuserapproved) || (int)$header->fapproval === 1;

    $codes  = $request->input('fitemcode', []);
    $sats   = $request->input('fsatuan', []);
    $qtys   = $request->input('fqty', []);
    $descs  = $request->input('fdesc', []);
    $ketdts = $request->input('fketdt', []);

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
      $code = trim((string)($codes[$i]  ?? ''));
      $sat  = trim((string)($sats[$i]   ?? ''));
      $qty  = $qtys[$i]  ?? null;
      $desc = $descs[$i] ?? null;
      $ket  = $ketdts[$i] ?? null;

      if ($code !== '' && $sat !== '' && is_numeric($qty) && (int)$qty >= 1) {
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
        if (!$wasApproved && $approveNow) {
          $setHeader['fapproval']     = 1;
          $setHeader['fuserapproved'] = $actor;
          $setHeader['fdateapproved'] = now();
        }
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
          DB::table('msprd')
            ->where('fproductcode', $code)
            ->update([
              'fminstock'  => DB::raw("CAST(fminstock AS INTEGER) - $qty"),
              'fupdatedat' => now(),
            ]);
        }
      }
    });

    return redirect()->route('tr_poh.index')->with('success', 'Permintaan pembelian berhasil diperbarui.');
  }

  public function destroy($fsatuanid)
  {
    $satuan = Tr_prh::findOrFail($fsatuanid);
    $satuan->delete();

    return redirect()
      ->route('tr_poh.index')
      ->with('success', 'Satuan berhasil dihapus.');
  }
}
