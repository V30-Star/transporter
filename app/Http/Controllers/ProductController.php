<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Groupproduct;  // Add this import to get the groups
use App\Models\Merek;         // If you have a model for "Merek"
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['fprdcode', 'fprdname', 'fsatuankecil', 'fminstock', 'fprdid', 'fnonactive'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fprdid';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $status = $request->query('status');

        $query = Product::query();

        if ($status === 'active') {
            $query->where('msprd.fnonactive', '0');
        } elseif ($status === 'nonactive') {
            $query->where('msprd.fnonactive', '1');
        }

        $products = $query
            ->orderBy($sortBy, $sortDir)
            ->get(['fprdcode', 'fprdname', 'fsatuankecil', 'fminstock', 'fprdid', 'fnonactive']);

        $canCreate = in_array('createProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteProduct', explode(',', session('user_restricted_permissions', '')));

        return view('product.index', compact('products', 'canCreate', 'canEdit', 'canDelete', 'status'));
    }

    public function suggestNames(Request $request)
    {
        $term = (string) $request->get('term', '');

        $q = DB::table('msprd')->whereNotNull('fprdname');

        if ($term !== '') {
            $q->where('fprdname', 'ILIKE', "%{$term}%");
        }

        $names = $q->distinct()
            ->orderBy('fprdname')
            ->limit(15)
            ->pluck('fprdname');

        return response()->json($names);
    }

    private function generateProductCode($groupId, $merekId): string
    {
        // 1. Pad ID grup dan merek (ggg.mmm)
        $paddedGroupId = str_pad($groupId, 3, '0', STR_PAD_LEFT);
        $paddedMerekId = str_pad($merekId, 3, '0', STR_PAD_LEFT);

        // 2. Buat prefix untuk pencarian (e.g., "001.002.")
        $prefix = $paddedGroupId . '.' . $paddedMerekId . '.';
        $prefixLength = strlen($prefix);

        // 3. Cari kode terakhir dengan prefix yang sama
        $lastCode = Product::where('fprdcode', 'like', $prefix . '%')
            // Urutkan berdasarkan angka di belakang prefix
            ->orderByRaw("CAST(SUBSTRING(fprdcode FROM " . ($prefixLength + 1) . ") AS INTEGER) DESC")
            ->value('fprdcode');

        // 4. Jika tidak ditemukan, mulai dari 1
        if (!$lastCode) {
            $newNumber = 1;
        } else {
            // 5. Jika ditemukan, ambil nomornya dan tambahkan 1
            // e.g., $lastCode = "001.002.000005"
            // substr($lastCode, $prefixLength) akan mengambil "000005"
            $number = (int)substr($lastCode, $prefixLength);
            $newNumber = $number + 1;
        }

        // 6. Kembalikan kode baru dengan padding 6 digit untuk sequence
        // e.g., "001.002.000006"
        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        // Get the group products and brands to pass to the view
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();
        $newProductCode = $this->generateProductCode($groups->first()->fgroupcode ?? 1, $merks->first()->fmerekid ?? 1);

        return view('product.create', compact('groups', 'merks', 'satuan', 'newProductCode'));
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate(
            [
                'fprdcode' => 'nullable|string',
                'fprdname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'string',
                'fgroupcode' => 'required', // Validate that fgroupcode exists in groups table
                'fmerek' => 'required', // Validate the Merek field
                'fsatuankecil' => '', // Validate Satuan 1 field
                'fsatuanbesar' => '', // Validate Satuan 2 field
                'fsatuanbesar2' => '', // Validate Satuan 3 field
                'fsatuandefault' => 'in:1,2,3', // Validate Satuan Default field
                'fqtykecil' => 'numeric', // Validate quantity for Satuan 1
                'fqtykecil2' => 'numeric', // Validate quantity for Satuan 3
                'fhpp' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel1' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel2' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel3' => '', // Validate if nonactive is checked
                'fhargajuallevel1' => '', // Validate if nonactive is checked
                'fhargajuallevel2' => '', // Validate if nonactive is checked
                'fhargajuallevel3' => '', // Validate if nonactive is checked
                'fminstock' => 'numeric', // Validate if nonactive is checked
            ],
            [
                'fprdcode.unique' => 'Kode Produk sudah ada, silakan gunakan kode yang lain.',
                'fprdname.required' => 'Nama Produk harus diisi.',
                'ftype.required' => 'Tipe Produk harus diisi.',
                'fbarcode.required' => 'Barcode Produk harus diisi.',
                'fgroupcode.required' => 'Group Produk harus dipilih.',
                'fmerek.required' => 'Merek harus dipilih.',
                'fsatuankecil.required' => 'Satuan Kecil harus dipilih.',
                'fsatuanbesar.required' => 'Satuan Besar harus dipilih.',
                'fsatuanbesar2.required' => 'Satuan Besar 2 harus dipilih.',
                'fsatuandefault.required' => 'Satuan Default harus dipilih.',
                'fqtykecil.required' => 'Qty Kecil harus diisi.',
                'fqtykecil2.required' => 'Qty Kecil 2 harus diisi.',
                'fhargasatuankecillevel1.required' => 'Harga Satuan 1 harus diisi.',
                'fhargasatuankecillevel2.required' => 'Harga Satuan 2 harus diisi.',
                'fhargasatuankecillevel3.required' => 'Harga Satuan 3 harus diisi.',
                'fhargajuallevel1.required' => 'Harga Satuan 1 harus diisi.',
                'fhargajuallevel2.required' => 'Harga Satuan 2 harus diisi.',
                'fminstock.required' => 'Harga Satuan 3 harus diisi.',
            ]
        );

        if (empty($request->fprdcode)) {
            // Panggil generator baru dengan ID grup dan merek dari data yang sudah tervalidasi
            $validated['fprdcode'] = $this->generateProductCode(
                $validated['fgroupcode'],
                $validated['fmerek']
            );
        }

        $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Product
        Product::create($validated);

        return redirect()
            ->route('product.create')
            ->with('success', 'Product berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();

        return view('product.edit', compact('product', 'groups', 'merks', 'satuan'));
    }

    public function update(Request $request, $fprdid)
    {
        // Validate the incoming data
        $validated = $request->validate(
            [
                'fprdcode' => "required|string|unique:msprd,fprdcode,{$fprdid},fprdid",
                'fprdname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'string',
                'fgroupcode' => 'required', // Validate that fgroupcode exists in groups table
                'fmerek' => 'required', // Validate the Merek field
                'fsatuankecil' => '', // Validate Satuan 1 field
                'fsatuanbesar' => '', // Validate Satuan 2 field
                'fsatuanbesar2' => '', // Validate Satuan 3 field
                'fsatuandefault' => 'in:1,2,3', // Validate Satuan Default field
                'fqtykecil' => 'numeric', // Validate quantity for Satuan 1
                'fqtykecil2' => 'numeric', // Validate quantity for Satuan 3
                'fhpp' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel1' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel2' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel3' => '', // Validate if nonactive is checked
                'fhargajuallevel1' => '', // Validate if nonactive is checked
                'fhargajuallevel2' => '', // Validate if nonactive is checked
                'fhargajuallevel3' => '', // Validate if nonactive is checked
                'fminstock' => 'numeric', // Validate if nonactive is checked
            ],
            [
                'fprdcode.unique' => 'Kode Produk sudah ada, silakan gunakan kode yang lain.',
                'fprdname.required' => 'Nama Produk harus diisi.',
                'ftype.required' => 'Tipe Produk harus diisi.',
                'fbarcode.required' => 'Barcode Produk harus diisi.',
                'fgroupcode.required' => 'Group Produk harus dipilih.',
                'fmerek.required' => 'Merek harus dipilih.',
                'fsatuankecil.required' => 'Satuan Kecil harus dipilih.',
                'fsatuanbesar.required' => 'Satuan Besar harus dipilih.',
                'fsatuanbesar2.required' => 'Satuan Besar 2 harus dipilih.',
                'fsatuandefault.required' => 'Satuan Default harus dipilih.',
                'fqtykecil.required' => 'Qty Kecil harus diisi.',
                'fqtykecil2.required' => 'Qty Kecil 2 harus diisi.',
                'fhargasatuankecillevel1.required' => 'Harga Satuan 1 harus diisi.',
                'fhargasatuankecillevel2.required' => 'Harga Satuan 2 harus diisi.',
                'fhargasatuankecillevel3.required' => 'Harga Satuan 3 harus diisi.',
                'fhargajuallevel1.required' => 'Harga Satuan 1 harus diisi.',
                'fhargajuallevel2.required' => 'Harga Satuan 2 harus diisi.',
                'fminstock.required' => 'Harga Satuan 3 harus diisi.',
            ]
        );

        if ($request->has('approve_now')) {
            $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;
        } else {
            $validated['fapproval'] = null;
        }

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $product = Product::findOrFail($fprdid);
        $product->update($validated);

        return redirect()
            ->route('product.index')
            ->with('success', 'Product berhasil di-update.');
    }

    public function destroy($fprdid)
    {
        try {
            // 1. Cari produknya dulu
            // findOrFail akan error jika produk tidak ada, langsung masuk ke catch
            $product = Product::findOrFail($fprdid);

            // 2. Lakukan pengecekan satu per satu MENGGUNAKAN RELASI

            // Cek ke tr_pod
            if ($product->trPods()->exists()) {
                return redirect()->route('product.index')
                    ->with('danger', 'Gagal hapus: Produk masih digunakan di data PO (tr_pod).');
            }

            // Cek ke tr_prd (asumsi dari fungsi Anda)
            if ($product->trPrds()->exists()) {
                return redirect()->route('product.index')
                    ->with('danger', 'Gagal hapus: Produk masih digunakan di data PR (tr_prd).');
            }

            // Cek ke trstockdt
            if ($product->trstockdts()->exists()) {
                return redirect()->route('product.index')
                    ->with('danger', 'Gagal hapus: Produk masih digunakan di data Transaksi Stok (trstockdt).');
            }

            // 3. Jika semua pengecekan lolos, baru hapus
            $product->delete();

            return redirect()->route('product.index')
                ->with('success', 'Produk berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Ini jika findOrFail gagal (produk tidak ditemukan)
            return redirect()->route('product.index')
                ->with('danger', 'Produk tidak ditemukan.');
        } catch (\Exception $e) {
            // Ini untuk menangkap error tak terduga lainnya
            return redirect()->route('product.index')
                ->with('error', 'Gagal menghapus produk: ' . $e->getMessage());
        }
    }
}
