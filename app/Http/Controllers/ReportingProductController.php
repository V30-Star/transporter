<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingProductController extends Controller
{
    public function index()
    {
        $products = DB::table('msprd')->orderBy('fprdcode', 'asc')->get();
        $groups = DB::table('msprd')->select('fgroupcode')->distinct()->whereNotNull('fgroupcode')->get();
        $mereks = DB::table('msprd')->select('fmerek')->distinct()->whereNotNull('fmerek')->get();
        $warehouses = DB::table('mswh')->where('fnonactive', '0')->orderBy('fwhname', 'asc')->get();

        return view('reportingproduct.index', compact('products', 'groups', 'mereks', 'warehouses'));
    }

    public function print(Request $request)
    {
        $data = $this->getData($request);

        $showCols = [
            'hpp' => $request->has('show_hpp'),
            'price1' => $request->has('show_price1'),
            'price2' => $request->has('show_price2'),
            'price3' => $request->has('show_price3'),
        ];

        $warehouseName = $request->warehouse
          ? DB::table('mswh')->where('fwhcode', $request->warehouse)->value('fwhname')
          : 'Semua Gudang';

        return view('reportingproduct.print', compact('data', 'showCols', 'warehouseName'));
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getData($request);

        $showCols = [
            'hpp' => $request->has('show_hpp'),
            'price1' => $request->has('show_price1'),
            'price2' => $request->has('show_price2'),
            'price3' => $request->has('show_price3'),
        ];

        $warehouseName = $request->warehouse
          ? DB::table('mswh')->where('fwhcode', $request->warehouse)->value('fwhname')
          : 'Semua Gudang';

        $filename = 'Master_Product_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        // --- Styles ---
        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleRow = new Style(fontBold: false);
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );

            return new Row($cells);
        };

        // --- Header Informasi ---
        $writer->addRow($makeRow(['MASTER PRODUCT LIST'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow([
            'Produk:',
            $request->prd_from
              ? '['.$request->prd_from.'] s/d ['.$request->prd_to.']'
              : 'Semua',
        ]));
        $writer->addRow($makeRow(['Gudang:', $warehouseName]));
        $writer->addRow($makeRow(['Group:', $request->group ?? 'Semua']));
        $writer->addRow($makeRow(['Merek:', $request->merek ?? 'Semua']));
        $writer->addRow($makeRow(['Filter Stok:', $request->has('only_stock') ? 'Hanya Stok > 0' : 'Semua']));
        $writer->addRow($makeRow([]));

        // --- Header Kolom (dinamis sesuai showCols) ---
        $headers = ['No', 'Kode Barang', 'Nama Barang', 'Satuan', 'Stok'];
        if ($showCols['hpp']) {
            $headers[] = 'HPP';
        }
        if ($showCols['price1']) {
            $headers[] = 'Harga Jual 1';
        }
        if ($showCols['price2']) {
            $headers[] = 'Harga Jual 2';
        }
        if ($showCols['price3']) {
            $headers[] = 'Harga Jual 3';
        }

        $writer->addRow($makeRow($headers, $styleHeader));

        $totalStok = 0;

        foreach ($data as $i => $row) {
            $totalStok += (float) $row->fstock;

            $cols = [
                $i + 1,
                $row->fprdcode,
                $row->fprdname,
                $row->fsatuankecil,
                (float) $row->fstock,
            ];

            if ($showCols['hpp']) {
                $cols[] = (float) $row->fhpp;
            }
            if ($showCols['price1']) {
                $cols[] = (float) $row->fhargajuallevel1;
            }
            if ($showCols['price2']) {
                $cols[] = (float) $row->fhargajuallevel2;
            }
            if ($showCols['price3']) {
                $cols[] = (float) $row->fhargajuallevel3;
            }

            $writer->addRow($makeRow($cols, $styleRow));
        }

        // --- Grand Total ---
        $writer->addRow($makeRow([]));

        $totalCols = array_fill(0, count($headers), '');
        $totalCols[0] = 'TOTAL JENIS BARANG';
        $totalCols[1] = count($data).' Items';
        $totalCols[4] = $totalStok;

        $writer->addRow($makeRow($totalCols, $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getData(Request $request)
    {
        $query = DB::table('msprd');

        if ($request->prd_from && $request->prd_to) {
            $query->whereBetween('fprdcode', [$request->prd_from, $request->prd_to]);
        }

        if ($request->group) {
            $query->where('fgroupcode', $request->group);
        }
        if ($request->merek) {
            $query->where('fmerek', $request->merek);
        }

        if ($request->has('only_stock')) {
            $query->whereRaw("CAST(COALESCE(fstock, '0') AS FLOAT) > 0");
        }

        $orderBy = $request->sort_by == 'name' ? 'fprdname' : 'fprdcode';

        return $query->orderBy($orderBy, 'asc')->get();
    }
}
