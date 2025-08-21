<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Groupproduct;  // Add this import to get the groups
use App\Models\Merek;         // If you have a model for "Merek"
use App\Models\Satuan;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = in_array($request->filter_by, ['fproductcode', 'fproductname'])
            ? $request->filter_by
            : 'fproductcode';

        $search = $request->search;

        $products = Product::when($search, function ($q) use ($filterBy, $search) {
            $q->where($filterBy, 'ILIKE', '%' . $search . '%');
        })
            ->orderBy('fproductid', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('product.index', compact('products', 'filterBy', 'search'));
    }
    private function generateProductCode(): string
    {
        $lastCode = Product::where('fproductcode', 'like', 'C-%')
            ->orderByRaw("CAST(SUBSTRING(fproductcode FROM 3) AS INTEGER) DESC")
            ->value('fproductcode');

        if (!$lastCode) {
            return 'C-01';
        }

        $number = (int)substr($lastCode, 2);
        $newNumber = $number + 1;

        return 'C-' . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        // Get the group products and brands to pass to the view
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();
        $newProductCode = $this->generateProductCode();

        return view('product.create', compact('groups', 'merks', 'satuan', 'newProductCode'));
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate(
            [
                'fproductcode' => 'nullable|string',
                'fproductname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'string',
                'fgroupcode' => 'required', // Validate the Group Produk field
                'fmerek' => '', // Validate the Merek field
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
                'fproductcode.unique' => 'Kode Produk sudah ada, silakan gunakan kode yang lain.',
                'fproductname.required' => 'Nama Produk harus diisi.',
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

        if (empty($request->fproductcode)) {
            $validated['fproductcode'] = $this->generateProductCode();
        }

        $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

        // Create the new Product
        Product::create($validated);

        return redirect()
            ->route('product.index')
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

    public function update(Request $request, $fproductid)
    {
        // Validate the incoming data
        $validated = $request->validate(
            [
                'fproductcode' => "required|string|unique:msproduct,fproductcode,{$fproductid},fproductid",
                'fproductname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'string',
                'fgroupcode' => 'required', // Validate the Group Produk field
                'fmerek' => '', // Validate the Merek field
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
                'fproductcode.unique' => 'Kode Produk sudah ada, silakan gunakan kode yang lain.',
                'fproductname.required' => 'Nama Produk harus diisi.',
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
        $product = Product::findOrFail($fproductid);
        $product->update($validated);

        return redirect()
            ->route('product.index')
            ->with('success', 'Product berhasil di-update.');
    }

    public function destroy($fproductid)
    {
        // Find and delete the Product
        $product = Product::findOrFail($fproductid);
        $product->delete();

        return redirect()
            ->route('product.index')
            ->with('success', 'Product berhasil dihapus.');
    }
}
