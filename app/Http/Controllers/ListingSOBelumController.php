<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Common\Entity\Style\Style;

class ListingSOBelumController extends Controller
{
    public function index()
    {
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();
        $groupPrd = DB::table('ms_groupprd')->orderBy('fgroupcode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get();

        return view('listingsobelum.index', compact('customers', 'groupPrd', 'products'));
    }

    public function printCustomer(Request $request)
    {
        return $this->printReport($request, 'customer');
    }

    public function printProduct(Request $request)
    {
        return $this->printReport($request, 'produk');
    }

    private function printReport(Request $request, $grouping)
    {
        $query = DB::table('trsomt as m')
            ->join('trsodt as d', 'm.fsono', '=', 'd.fsono')
            ->join('msprd as p', 'd.fitemid', '=', 'p.fprdid')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomerid')
            ->select(
                'm.fsono',
                'm.fsodate',
                'm.fcustno',
                'c.fcustomername',
                'p.fprdcode',
                'p.fprdname',
                'd.fsatuan',
                'd.fpricenet',
                'p.fstock',
                'p.fqtykecil',
                'p.fsatuanbesar',
                DB::raw("CASE WHEN d.fsatuan = p.fsatuanbesar THEN d.fqty / p.fqtykecil ELSE d.fqty END as fqty")
            )
            ->where('d.fqty', '>', 0)
            ->where('m.fclose', '0');

        if ($request->date_from) $query->where('m.fsodate', '>=', $request->date_from);
        if ($request->date_to)   $query->where('m.fsodate', '<=', $request->date_to . ' 23:59:59');

        if ($request->cust_from && $request->cust_to) {
            $query->whereBetween('c.fcustomercode', [$request->cust_from, $request->cust_to]);
        }

        if ($request->group_prd) {
            $query->where('p.fgroupcode', $request->group_prd);
        }

        if ($request->selected_products) {
            $prdArray = explode(',', $request->selected_products);
            $query->whereIn('p.fprdcode', $prdArray);
        }

        if ($request->has('only_stok')) {
            $query->where('p.fstock', '>', 0);
        }

        if ($grouping == 'produk') {
            $query->orderBy('p.fprdcode')->orderBy('m.fsodate');
            $results = $query->get()->groupBy('fprdcode');
            $view = 'listingsobelum.printProduct';
        } else {
            $query->orderBy('c.fcustomercode')->orderBy('m.fsono');
            $results = $query->get()->groupBy('fcustomercode');
            $view = 'listingsobelum.printCustomer';
        }

        $rows = $query->get();

        $chunkedData = $rows->chunk(30)->map(function ($chunk) use ($grouping) {
            return $grouping == 'produk'
                ? $chunk->groupBy('fprdcode')
                : $chunk->groupBy('fcustomercode');
        });

        $totalPages = $chunkedData->count();

        return view($view, [
            'chunkedData'  => $chunkedData,
            'totalPages'   => $totalPages,
            'user_session' => auth()->user(),
            'request'      => $request
        ]);
    }

    public function excelCustomer(Request $request)
    {
        $results = $this->getQueryResult($request, 'customer');

        $filename = "SO_Belum_Dikirim_Customer_" . date('YmdHis') . ".xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $styleTitle      = new Style(fontBold: true, fontSize: 14);
        $styleHeader     = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup      = new Style(fontBold: true, backgroundColor: 'FFE6E6');
        $styleSubtotal   = new Style(fontBold: true, backgroundColor: 'FFF0F0');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');
        $styleRed        = new Style(fontColor: 'CC0000', fontBold: true);
        $styleBlue       = new Style(fontColor: '0000CC');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['SO YANG BELUM DIKIRIM (BY CUSTOMER)'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y') . '  Jam: ' . date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from . ' s/d ' . $request->date_to]));
        $writer->addRow($makeRow(['Customer:', $request->cust_from ? $request->cust_from . ' s/d ' . $request->cust_to : 'Semua']));
        $writer->addRow($makeRow(['Operator:', auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No. SO',
            'Tanggal',
            'Nama Barang',
            'Satuan',
            '@ Harga',
            'Qty. Sisa',
            'Qty. Stok'
        ], $styleHeader));

        $grandTotalQty = 0;
        $no = 0;

        foreach ($results as $custId => $rows) {
            // Group Customer
            $writer->addRow($makeRow([
                'Customer: ' . $rows->first()->fcustomername,
                '',
                '',
                '',
                '',
                '',
                ''
            ], $styleGroup));

            $custQty = 0;

            foreach ($rows as $row) {
                $custQty += $row->fqty;
                $writer->addRow($makeRow([
                    $row->fsono,
                    date('d/m/Y', strtotime($row->fsodate)),
                    $row->fprdname,
                    $row->fsatuan,
                    (float) $row->fpricenet,
                    (float) $row->fqty,
                    (float) $row->fstock,
                ]));
            }

            // Subtotal per Customer
            $writer->addRow($makeRow([
                'Total ' . $rows->first()->fcustomername,
                '',
                '',
                '',
                '',
                $custQty,
                ''
            ], $styleSubtotal));

            $grandTotalQty += $custQty;
        }

        // Grand Total
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL QTY SO BELUM DIKIRIM',
            '',
            '',
            '',
            '',
            $grandTotalQty,
            ''
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function excelProduct(Request $request)
    {
        $results = $this->getQueryResult($request, 'produk');

        $filename = "SO_Belum_Dikirim_Produk_" . date('YmdHis') . ".xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $styleTitle      = new Style(fontBold: true, fontSize: 14);
        $styleHeader     = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup      = new Style(fontBold: true, backgroundColor: 'FFE6E6');
        $styleSubtotal   = new Style(fontBold: true, backgroundColor: 'FFF0F0');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['SO YANG BELUM DIKIRIM (BY PRODUK)'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y') . '  Jam: ' . date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from . ' s/d ' . $request->date_to]));
        $writer->addRow($makeRow(['Customer:', $request->cust_from ? $request->cust_from . ' s/d ' . $request->cust_to : 'Semua']));
        $writer->addRow($makeRow(['Operator:', auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No. SO',
            'Tanggal',
            'Nama Customer',
            'Satuan',
            '@ Harga',
            'Qty. Sisa',
            'Qty. Stok'
        ], $styleHeader));

        $grandTotalQty = 0;

        foreach ($results as $prdCode => $rows) {
            // Group Produk
            $writer->addRow($makeRow([
                'Produk: [' . $prdCode . '] ' . $rows->first()->fprdname,
                '',
                '',
                '',
                '',
                '',
                ''
            ], $styleGroup));

            $prdQty = 0;

            foreach ($rows as $row) {
                $prdQty += $row->fqty;
                $writer->addRow($makeRow([
                    $row->fsono,
                    date('d/m/Y', strtotime($row->fsodate)),
                    $row->fcustomername,
                    $row->fsatuan,
                    (float) $row->fpricenet,
                    (float) $row->fqty,
                    (float) $row->fstock,
                ]));
            }

            // Subtotal per Produk
            $writer->addRow($makeRow([
                'Total [' . $prdCode . '] ' . $rows->first()->fprdname,
                '',
                '',
                '',
                '',
                $prdQty,
                ''
            ], $styleSubtotal));

            $grandTotalQty += $prdQty;
        }

        // Grand Total
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL QTY SO BELUM DIKIRIM',
            '',
            '',
            '',
            '',
            $grandTotalQty,
            ''
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getQueryResult(Request $request, string $grouping)
    {
        $query = DB::table('trsomt as m')
            ->join('trsodt as d', 'm.fsono', '=', 'd.fsono')
            ->join('msprd as p', 'd.fitemid', '=', 'p.fprdid')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomerid')
            ->select(
                'm.fsono',
                'm.fsodate',
                'm.fcustno',
                'c.fcustomername',
                'p.fprdcode',
                'p.fprdname',
                'd.fsatuan',
                'd.fpricenet',
                'p.fstock',
                'p.fqtykecil',
                'p.fsatuanbesar',
                DB::raw("CASE WHEN d.fsatuan = p.fsatuanbesar THEN d.fqty / p.fqtykecil ELSE d.fqty END as fqty")
            )
            ->where('d.fqty', '>', 0)
            ->where('m.fclose', '0');

        if ($request->date_from) $query->where('m.fsodate', '>=', $request->date_from);
        if ($request->date_to)   $query->where('m.fsodate', '<=', $request->date_to . ' 23:59:59');
        if ($request->cust_from && $request->cust_to)
            $query->whereBetween('c.fcustomercode', [$request->cust_from, $request->cust_to]);
        if ($request->group_prd)
            $query->where('p.fgroupcode', $request->group_prd);
        if ($request->selected_products)
            $query->whereIn('p.fprdcode', explode(',', $request->selected_products));
        if ($request->has('only_stok'))
            $query->where('p.fstock', '>', 0);

        if ($grouping == 'produk') {
            return $query->orderBy('p.fprdcode')->orderBy('m.fsodate')->get()->groupBy('fprdcode');
        } else {
            return $query->orderBy('c.fcustomercode')->orderBy('m.fsono')->get()->groupBy('fcustomercode');
        }
    }
}
