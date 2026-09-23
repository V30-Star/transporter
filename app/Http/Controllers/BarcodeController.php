<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarcodeController extends Controller
{
    private function checkAccess()
    {
        $rawPermissions = session('user_restricted_permissions');
        if ($rawPermissions !== null && trim((string) $rawPermissions) !== '') {
            $permissions = array_filter(array_map('trim', explode(',', (string) $rawPermissions)));
            if (!empty($permissions) && !in_array('viewProduct', $permissions, true)) {
                abort(403, 'Anda tidak memiliki akses ke fitur cetak barcode.');
            }
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $companyName = function_exists('company_name') ? company_name() : 'Transporter';

        // Check if preloaded product is passed via query string
        $preloaded = null;
        if ($request->filled('fprdcode') || $request->filled('fprdid')) {
            $product = Product::query()
                ->where('fprdcode', $request->input('fprdcode'))
                ->orWhere('fprdid', $request->input('fprdid'))
                ->first();

            if ($product) {
                $preloaded = [
                    'fprdid' => $product->fprdid,
                    'fprdcode' => $product->fprdcode,
                    'fprdname' => $product->fprdname,
                    'fbarcode' => !empty(trim((string) $product->fbarcode)) ? trim((string) $product->fbarcode) : trim((string) $product->fprdcode),
                    'price' => (float) ($product->fhargajuallevel1 ?? 0),
                    'satuan' => $product->fsatuankecil ?? '',
                    'qty' => max(1, (int) $request->input('qty', 1)),
                ];
            }
        }

        return view('barcode.index', compact('companyName', 'preloaded'));
    }

    public function searchProducts(Request $request)
    {
        $this->checkAccess();

        $search = trim((string) $request->input('q', ''));

        $limit = max(10, min(200, (int) $request->input('limit', 50)));

        $query = Product::query()
            ->select('fprdid', 'fprdcode', 'fprdname', 'fbarcode', 'fhargajuallevel1', 'fsatuankecil', 'fstok')
            ->whereRaw("COALESCE(TRIM(CAST(msprd.fnonactive AS TEXT)), '0') != '1'");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('fprdcode', 'ILIKE', "%{$search}%")
                  ->orWhere('fprdname', 'ILIKE', "%{$search}%")
                  ->orWhere('fbarcode', 'ILIKE', "%{$search}%");
            });
        }

        $items = $query->limit($limit)->get()->map(function ($item) {
            $barcode = !empty(trim((string) $item->fbarcode)) ? trim((string) $item->fbarcode) : trim((string) $item->fprdcode);
            return [
                'id' => $item->fprdid,
                'fprdcode' => $item->fprdcode,
                'fprdname' => $item->fprdname,
                'fbarcode' => $barcode,
                'price' => (float) ($item->fhargajuallevel1 ?? 0),
                'satuan' => $item->fsatuankecil ?? '',
                'stock' => (float) ($item->fstok ?? 0),
            ];
        });

        return response()->json($items);
    }

    public function printLabels(Request $request)
    {
        $this->checkAccess();

        $labelWidth = max(10, (float) $request->input('label_width', 33));
        $labelHeight = max(10, (float) $request->input('label_height', 15));
        $columns = max(1, min(6, (int) $request->input('columns', 3)));
        $gapX = max(0, (float) $request->input('gap_x', 2));
        $gapY = max(0, (float) $request->input('gap_y', 0));
        $showCompany = (bool) $request->input('show_company', true);
        $companyName = trim((string) $request->input('company_name', function_exists('company_name') ? company_name() : ''));
        $showName = (bool) $request->input('show_name', true);
        $showPrice = (bool) $request->input('show_price', true);
        $showCode = (bool) $request->input('show_code', true);
        $barcodeHeight = max(15, min(80, (int) $request->input('barcode_height', 24)));
        $fontSize = max(6, min(14, (float) $request->input('font_size', 7.5)));

        $rawItems = $request->input('items', []);
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true) ?: [];
        }

        $labels = [];
        foreach ($rawItems as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $barcode = !empty(trim((string) ($item['fbarcode'] ?? ''))) 
                ? trim((string) $item['fbarcode']) 
                : trim((string) ($item['fprdcode'] ?? ''));

            $price = (float) ($item['price'] ?? 0);
            $name = trim((string) ($item['fprdname'] ?? ''));
            $code = trim((string) ($item['fprdcode'] ?? ''));

            for ($i = 0; $i < $qty; $i++) {
                $labels[] = [
                    'code' => $code,
                    'name' => $name,
                    'barcode' => $barcode,
                    'price' => $price,
                ];
            }
        }

        // Calculate total page width for continuous roll
        $pageWidth = ($labelWidth * $columns) + ($gapX * ($columns - 1));
        $pageHeight = $labelHeight;

        // Group into rows by column count
        $rows = array_chunk($labels, $columns);

        return view('barcode.print', compact(
            'rows',
            'labelWidth',
            'labelHeight',
            'columns',
            'gapX',
            'gapY',
            'pageWidth',
            'pageHeight',
            'showCompany',
            'companyName',
            'showName',
            'showPrice',
            'showCode',
            'barcodeHeight',
            'fontSize'
        ));
    }

    public function printDirect(Request $request)
    {
        $this->checkAccess();

        // Convenience wrapper for GET requests
        $fprdcode = $request->input('fprdcode');
        $qty = max(1, (int) $request->input('qty', 1));

        $item = null;
        if ($fprdcode) {
            $product = Product::where('fprdcode', $fprdcode)->first();
            if ($product) {
                $barcode = !empty(trim((string) $product->fbarcode)) ? trim((string) $product->fbarcode) : trim((string) $product->fprdcode);
                $item = [
                    'fprdcode' => $product->fprdcode,
                    'fprdname' => $product->fprdname,
                    'fbarcode' => $barcode,
                    'price' => (float) ($product->fhargajuallevel1 ?? 0),
                    'qty' => $qty,
                ];
            }
        }

        $items = $item ? [$item] : [];

        $request->merge([
            'items' => $items,
            'label_width' => $request->input('label_width', 33),
            'label_height' => $request->input('label_height', 15),
            'columns' => $request->input('columns', 3),
        ]);

        return $this->printLabels($request);
    }
}
