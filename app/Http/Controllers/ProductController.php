<?php

namespace App\Http\Controllers;

use App\Models\Groupproduct;
use App\Models\Merek;  // Add this import to get the groups
use App\Models\Product;         // If you have a model for "Merek"
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::query()
                ->from('msprd')
                ->leftJoin('msmerek', 'msmerek.fmerekid', '=', 'msprd.fmerek');

            // Filter Status
            $status = $request->input('status', 'active'); // default active
            if ($status === 'active') {
                $query->where('msprd.fnonactive', '0');
            } elseif ($status === 'nonactive') {
                $query->where('msprd.fnonactive', '1');
            }
            // 'all' = tidak ada filter

            // Total records
            $totalRecords = Product::count();
            $totalAfterStatusFilter = (clone $query)->count();

            // Pencarian global
            $searchableColumns = ['msprd.fprdcode', 'msprd.fprdname', 'msprd.fsatuankecil', 'msprd.fminstock', 'msmerek.fmerekname'];
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'ilike', "%{$search}%"); // PostgreSQL: ilike (case-insensitive)
                    }
                });
            }

            // Pencarian per kolom
            $columnFields = [
                'msprd.fprdcode',
                'msprd.fprdname',
                'msmerek.fmerekname',
                'msprd.fsatuankecil',
                'msprd.fminstock',
            ];
            foreach ($columnFields as $index => $field) {
                $colSearch = $request->input("columns.{$index}.search.value");
                if ($colSearch !== null && $colSearch !== '') {
                    $query->where($field, 'ilike', "%{$colSearch}%");
                }
            }

            $filteredRecords = (clone $query)->count();

            // Sorting
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');
            $columns = [
                'msprd.fprdcode',
                'msprd.fprdname',
                'msmerek.fmerekname',
                'msprd.fsatuankecil',
                'msprd.fminstock',
                'msprd.fnonactive',
            ];
            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDir);
            }

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);

            $products = $query->skip($start)->take($length)->get([
                'msprd.fprdcode',
                'msprd.fprdname',
                'msprd.fsatuankecil',
                'msprd.fminstock',
                'msprd.fprdid',
                'msprd.fnonactive',
                'msprd.fmerek',
                'msmerek.fmerekname AS merek_name',
            ]);

            // Format data untuk DataTables
            $data = $products->map(function ($item) {
                $isActive = (string) $item->fnonactive === '0';
                $statusBadge = $isActive
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Active</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-200 text-red-700">Non Active</span>';

                return [
                    'fprdcode' => $item->fprdcode,
                    'fprdname' => $item->fprdname,
                    'fmerek' => $item->merek_name,
                    'fsatuankecil' => $item->fsatuankecil,
                    'fminstock' => $item->fminstock,
                    'status' => $statusBadge,
                    'fprdid' => $item->fprdid,
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        // Non-AJAX: render view
        $canCreate = in_array('createProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteProduct', explode(',', session('user_restricted_permissions', '')));

        return view('product.index', compact('canCreate', 'canEdit', 'canDelete'));
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

    public function suggestCodes(Request $request)
    {
        $term = (string) $request->get('term', '');

        $q = DB::table('msprd')->whereNotNull('fprdcode');

        if ($term !== '') {
            $q->where('fprdcode', 'ILIKE', "%{$term}%");
        }

        $codes = $q->distinct()
            ->orderBy('fprdcode')
            ->limit(15)
            ->pluck('fprdcode');

        return response()->json($codes);
    }

    private function generateProductCode($groupId, $merekId): string
    {
        // 1. Pad ID grup dan merek (ggg.mmm)
        $paddedGroupId = str_pad($groupId, 3, '0', STR_PAD_LEFT);
        $paddedMerekId = str_pad($merekId, 3, '0', STR_PAD_LEFT);

        // 2. Buat prefix untuk pencarian (e.g., "001.002.")
        $prefix = $paddedGroupId.'.'.$paddedMerekId.'.';
        $prefixLength = strlen($prefix);

        // 3. Cari kode terakhir dengan prefix yang sama
        $lastCode = Product::where('fprdcode', 'like', $prefix.'%')
            // Urutkan berdasarkan angka di belakang prefix
            ->orderByRaw('CAST(SUBSTRING(fprdcode FROM '.($prefixLength + 1).') AS INTEGER) DESC')
            ->value('fprdcode');

        // 4. Jika tidak ditemukan, mulai dari 1
        if (! $lastCode) {
            $newNumber = 1;
        } else {
            // 5. Jika ditemukan, ambil nomornya dan tambahkan 1
            // e.g., $lastCode = "001.002.000005"
            // substr($lastCode, $prefixLength) akan mengambil "000005"
            $number = (int) substr($lastCode, $prefixLength);
            $newNumber = $number + 1;
        }

        // 6. Kembalikan kode baru dengan padding 6 digit untuk sequence
        // e.g., "001.002.000006"
        return $prefix.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
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

        $validated = $request->validate(
            [
                'fprdcode' => 'nullable|string|unique:msprd,fprdcode',
                'fprdname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'nullable',
                'fgroupcode' => 'required', // Validate that fgroupcode exists in groups table
                'fmerek' => 'required', // Validate the Merek field
                'fsatuankecil' => '', // Validate Satuan 1 field
                'fsatuanbesar' => [
                    'nullable',               // Boleh kosong
                    'string',
                    'different:fsatuankecil',  // Jika diisi, harus beda dengan fsatuankecil
                ],

                'fsatuanbesar2' => [
                    'nullable',               // Boleh kosong
                    'string',
                    'different:fsatuankecil', // Jika diisi, harus beda dengan fsatuankecil
                    'different:fsatuanbesar',  // Jika diisi, harus beda dengan fsatuanbesar
                ],
                'fsatuandefault' => 'in:1,2,3', // Validate Satuan Default field
                'fqtykecil' => 'numeric', // Validate quantity for Satuan 1
                'fqtykecil2' => 'numeric', // Validate quantity for Satuan 3
                'fhargasatuankecillevel1' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel2' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel3' => '', // Validate if nonactive is checked
                'fhargajuallevel1' => '', // HJ. Kecil Level 1
                'fhargajuallevel2' => '', // HJ. Kecil Level 2
                'fhargajuallevel3' => '', // HJ. Kecil Level 3
                'fhargajual2level1' => '', // HJ. Besar Level 1
                'fhargajual2level2' => '', // HJ. Besar Level 2
                'fhargajual2level3' => '', // HJ. Besar Level 3
                'fhargajual3level1' => '', // HJ <PCS> Level 1
                'fhargajual3level2' => '', // HJ <CTN> Level 1
                'fhargajual3level3' => '', // HJ <DUS> Level 1
                'fminstock' => 'numeric',
                'fhpp' => 'nullable',
                'fhpp2' => 'nullable',
                'fhpp3' => 'nullable',
            ],
            [
                'fprdcode.unique' => 'Kode Produk sudah ada',
                'fprdcode.required' => 'Kode Produk harus diisi.',
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
                'fhargajuallevel1.required' => 'Harga Jual Kecil Level 1 harus diisi.',
                'fhargajuallevel2.required' => 'Harga Jual Kecil Level 2 harus diisi.',
                'fhargajuallevel3.required' => 'Harga Jual Kecil Level 3 harus diisi.',
                'fminstock.required' => 'Min Stok harus diisi.',
                'fsatuanbesar.different' => 'Satuan Besar tidak boleh sama dengan Satuan Kecil.',
                'fsatuanbesar2.different' => 'Satuan Besar 2 tidak boleh sama dengan Satuan Kecil atau Satuan Besar.',
            ]
        );

        $validated['fprdname'] = strtoupper($validated['fprdname']);

        if (empty($request->fprdcode)) {
            // Jika input fprdcode kosong, maka generate otomatis
            $validated['fprdcode'] = $this->generateProductCode(
                $validated['fgroupcode'],
                $validated['fmerek']
            );
        } else {
            // Jika user isi manual, gunakan input tersebut (sudah ada di $validated dari hasil validasi)
            $validated['fprdcode'] = $request->fprdcode;
        }

        $validated['fhpp'] = preg_replace('/[^0-9.]/', '', $request->fhpp);
        $validated['fhpp2'] = preg_replace('/[^0-9.]/', '', $request->fhpp2);
        $validated['fhpp3'] = preg_replace('/[^0-9.]/', '', $request->fhpp3);

        $validated['fhargajuallevel1'] = preg_replace('/[^0-9.]/', '', $request->fhargajuallevel1);
        $validated['fhargajuallevel2'] = preg_replace('/[^0-9.]/', '', $request->fhargajuallevel2);
        $validated['fhargajuallevel3'] = preg_replace('/[^0-9.]/', '', $request->fhargajuallevel3);
        $validated['fhargajual2level1'] = preg_replace('/[^0-9.]/', '', $request->fhargajual2level1);
        $validated['fhargajual2level2'] = preg_replace('/[^0-9.]/', '', $request->fhargajual2level2);
        $validated['fhargajual2level3'] = preg_replace('/[^0-9.]/', '', $request->fhargajual2level3);
        $validated['fhargajual3level1'] = preg_replace('/[^0-9.]/', '', $request->fhargajual3level1);
        $validated['fhargajual3level2'] = preg_replace('/[^0-9.]/', '', $request->fhargajual3level2);
        $validated['fhargajual3level3'] = preg_replace('/[^0-9.]/', '', $request->fhargajual3level3);

        $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
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

        return view('product.edit', [
            'product' => $product,
            'groups' => $groups,
            'merks' => $merks,
            'satuan' => $satuan,
            'action' => 'edit',
        ]);
    }

    public function view($id)
    {
        $product = Product::findOrFail($id);
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();

        return view('product.view', [
            'product' => $product,
            'groups' => $groups,
            'merks' => $merks,
            'satuan' => $satuan,
        ]);
    }

    public function update(Request $request, $fprdid)
    {

        // Validate the incoming data
        $validated = $request->validate(
            [
                'fprdcode' => "required|string|unique:msprd,fprdcode,{$fprdid},fprdid",
                'fprdname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'nullable',
                'fgroupcode' => 'required', // Validate that fgroupcode exists in groups table
                'fmerek' => 'required', // Validate the Merek field
                'fsatuankecil' => '', // Validate Satuan 1 field
                'fsatuanbesar' => [
                    'nullable',               // Boleh kosong
                    'string',
                    'different:fsatuankecil',  // Jika diisi, harus beda dengan fsatuankecil
                ],

                'fsatuanbesar2' => [
                    'nullable',               // Boleh kosong
                    'string',
                    'different:fsatuankecil', // Jika diisi, harus beda dengan fsatuankecil
                    'different:fsatuanbesar',  // Jika diisi, harus beda dengan fsatuanbesar
                ],
                'fsatuandefault' => 'in:1,2,3', // Validate Satuan Default field
                'fqtykecil' => 'numeric', // Validate quantity for Satuan 1
                'fqtykecil2' => 'numeric', // Validate quantity for Satuan 3
                'fhargasatuankecillevel1' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel2' => '', // Validate if nonactive is checked
                'fhargasatuankecillevel3' => '', // Validate if nonactive is checked
                'fhargajuallevel1' => '', // HJ. Kecil Level 1
                'fhargajuallevel2' => '', // HJ. Kecil Level 2
                'fhargajuallevel3' => '', // HJ. Kecil Level 3
                'fhargajual2level1' => '', // HJ. Besar Level 1
                'fhargajual2level2' => '', // HJ. Besar Level 2
                'fhargajual2level3' => '', // HJ. Besar Level 3
                'fhargajual3level1' => '', // HJ <PCS> Level 1
                'fhargajual3level2' => '', // HJ <CTN> Level 1
                'fhargajual3level3' => '', // HJ <DUS> Level 1
                'fminstock' => 'numeric',
                'fhpp' => 'nullable',
                'fhpp2' => 'nullable',
                'fhpp3' => 'nullable',
            ],
            [
                'fprdcode.unique' => 'Kode Produk sudah ada',
                'fprdname.required' => 'Nama Produk harus diisi.',
                'ftype.required' => 'Tipe Produk harus diisi.',
                'fbarcode.required' => 'Barcode Produk harus diisi.',
                'fgroupcode.required' => 'Group Produk harus dipilih.',
                'fmerek.required' => 'Merek harus dipilih.',
                'fsatuankecil.required' => 'Satuan Kecil harus diisi.',
                'fsatuanbesar.required' => 'Satuan Besar harus dipilih.',
                'fsatuanbesar2.required' => 'Satuan Besar 2 harus dipilih.',
                'fsatuandefault.required' => 'Satuan Default harus diisi.',
                'fqtykecil.required' => 'Qty Kecil harus diisi.',
                'fqtykecil2.required' => 'Qty Kecil 2 harus diisi.',
                'fhargasatuankecillevel1.required' => 'Harga Satuan 1 harus diisi.',
                'fhargasatuankecillevel2.required' => 'Harga Satuan 2 harus diisi.',
                'fhargasatuankecillevel3.required' => 'Harga Satuan 3 harus diisi.',
                'fhargajuallevel1.required' => 'Harga Jual Kecil Level 1 harus diisi.',
                'fhargajuallevel2.required' => 'Harga Jual Kecil Level 2 harus diisi.',
                'fhargajuallevel3.required' => 'Harga Jual Kecil Level 3 harus diisi.',
                'fminstock.required' => 'Min Stok harus diisi.',
                'fsatuanbesar.different' => 'Satuan Besar tidak boleh sama dengan Satuan Kecil.',
                'fsatuanbesar2.different' => 'Satuan Besar 2 tidak boleh sama dengan Satuan Kecil atau Satuan Besar.',
            ]
        );

        $validated['fprdcode'] = strtoupper($validated['fprdcode']);
        $validated['fprdname'] = strtoupper($validated['fprdname']);

        if ($request->has('approve_now')) {
            $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;
        } else {
            $validated['fapproval'] = null;
        }

        $validated['fhpp'] = preg_replace('/[^0-9.]/', '', $request->fhpp);
        $validated['fhpp2'] = preg_replace('/[^0-9.]/', '', $request->fhpp2);
        $validated['fhpp3'] = preg_replace('/[^0-9.]/', '', $request->fhpp3);

        $validated['fhargajuallevel1'] = preg_replace('/[^0-9.]/', '', $request->fhargajuallevel1);
        $validated['fhargajuallevel2'] = preg_replace('/[^0-9.]/', '', $request->fhargajuallevel2);
        $validated['fhargajuallevel3'] = preg_replace('/[^0-9.]/', '', $request->fhargajuallevel3);
        $validated['fhargajual2level1'] = preg_replace('/[^0-9.]/', '', $request->fhargajual2level1);
        $validated['fhargajual2level2'] = preg_replace('/[^0-9.]/', '', $request->fhargajual2level2);
        $validated['fhargajual2level3'] = preg_replace('/[^0-9.]/', '', $request->fhargajual2level3);
        $validated['fhargajual3level1'] = preg_replace('/[^0-9.]/', '', $request->fhargajual3level1);
        $validated['fhargajual3level2'] = preg_replace('/[^0-9.]/', '', $request->fhargajual3level2);
        $validated['fhargajual3level3'] = preg_replace('/[^0-9.]/', '', $request->fhargajual3level3);

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
        $product = Product::findOrFail($fprdid);
        $product->update($validated);

        return redirect()
            ->route('product.index')
            ->with('success', 'Product berhasil di-update.');
    }

    public function delete($fprdid)
    {
        $product = Product::findOrFail($fprdid);
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();

        return view('product.edit', [
            'product' => $product,
            'groups' => $groups,
            'merks' => $merks,
            'satuan' => $satuan,
            'action' => 'delete',
        ]);
    }

    public function destroy($fprdid)
    {
        try {
            $product = Product::findOrFail($fprdid);

            if ($product->trPods()->exists()) {
                return response()->json(['message' => 'Gagal hapus: Produk masih digunakan di data PO.'], 422);
            }

            if ($product->trPrds()->exists()) {
                return response()->json(['message' => 'Gagal hapus: Produk masih digunakan di data PR.'], 422);
            }

            if ($product->trstockdts()->exists()) {
                return response()->json(['message' => 'Gagal hapus: Produk masih digunakan di Transaksi Stok.'], 422);
            }

            $product->delete();

            return response()->json(['message' => 'Data produk '.$product->fprdname.' berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus: '.$e->getMessage()], 500);
        }
    }
}
