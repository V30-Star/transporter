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
        $groups = Groupproduct::all();  // Fetch all group products
        $merks = Merek::all();  // Fetch all brands, assuming Merek is the brand model\
        $satuan = Satuan::all();  // Fetch all units, assuming Satuan is the unit model
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
                'ftype' => 'required|string',
                'fbarcode' => 'required|string',
                'fgroupcode' => 'required', // Validate the Group Produk field
                'fmerek' => 'required', // Validate the Merek field
                'fsatuankecil' => 'required', // Validate Satuan 1 field
                'fsatuanbesar' => 'required', // Validate Satuan 2 field
                'fsatuanbesar2' => 'required', // Validate Satuan 3 field
                'fsatuandefault' => 'required|in:1,2,3', // Validate Satuan Default field
                'fqtykecil' => 'required|numeric', // Validate quantity for Satuan 1
                'fqtykecil2' => 'required|numeric', // Validate quantity for Satuan 3
                'fhpp' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargasatuankecillevel1' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargasatuankecillevel2' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargasatuankecillevel3' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargajuallevel1' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargajuallevel2' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargajuallevel3' => 'nullable|numeric', // Validate if nonactive is checked
                'fminstock' => 'nullable|numeric', // Validate if nonactive is checked
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
            ]
        );

        if (empty($request->fproductcode)) {
            $validated['fproductcode'] = $this->generateProductCode();
        }

        $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;

        $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fcreatedat'] = now(); // Use the current time

        $validated['fnonactive'] = '0';

        // Create the new Product
        Product::create($validated);

        return redirect()
            ->route('product.index')
            ->with('success', 'Product berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $groups = Groupproduct::all();
        $merks = Merek::all();
        $satuan = Satuan::all();

        return view('product.edit', compact('product', 'groups', 'merks', 'satuan'));
    }

    public function update(Request $request, $fproductid)
    {
        // Validate the incoming data
        $validated = $request->validate(
            [
                'fproductcode' => "required|string|unique:msproduct,fproductcode,{$fproductid},fproductid",
                'fproductname' => 'required|string',
                'ftype' => 'required|string',
                'fbarcode' => 'required|string',
                'fgroupcode' => 'required', // Validate the Group Produk field
                'fmerek' => 'required', // Validate the Merek field
                'fsatuankecil' => 'required', // Validate Satuan 1 field
                'fsatuanbesar' => 'required', // Validate Satuan 2 field
                'fsatuanbesar2' => 'required', // Validate Satuan 3 field
                'fsatuandefault' => 'required|in:1,2,3', // Validate Satuan Default field
                'fqtykecil' => 'required|numeric', // Validate quantity for Satuan 1
                'fqtykecil2' => 'required|numeric', // Validate quantity for Satuan 3
                'fhpp' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargasatuankecillevel1' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargasatuankecillevel2' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargasatuankecillevel3' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargajuallevel1' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargajuallevel2' => 'nullable|numeric', // Validate if nonactive is checked
                'fhargajuallevel3' => 'nullable|numeric', // Validate if nonactive is checked
                'fminstock' => 'nullable|numeric', // Validate if nonactive is checked
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
            ]
        );

        if ($request->has('approve_now')) {
            $validated['fapproval'] = auth('sysuser')->user()->fname ?? null;
        } else {
            $validated['fapproval'] = null;
        }

        $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
        $validated['fupdatedat'] = now(); // Use the current time

        $validated['fnonactive'] = '0';
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
