<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingSOBelumController extends Controller
{
    public function index()
    {
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();
        $groupPrd = DB::table('ms_groupprd')->orderBy('fgroupcode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get();
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingsobelum.index', compact('customers', 'groupPrd', 'products', 'branches', 'isAuthorized', 'userBranchCode'));
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
        $results = $this->getQueryResult($request, $grouping);

        if ($grouping == 'produk') {
            $view = 'listingsobelum.printProduct';
        } else {
            $view = 'listingsobelum.printCustomer';
        }

        return view($view, [
            'soData' => $results,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
            'request' => $request,
            'reportType' => $request->input('report_type', 'detail'),
        ]);
    }

    public function excelCustomer(Request $request)
    {
        $results = $this->getQueryResult($request, 'customer');

        $filename = 'SO_Belum_Dikirim_Customer_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup = new Style(fontBold: true, backgroundColor: 'FFE6E6');
        $styleSubtotal = new Style(fontBold: true, backgroundColor: 'FFF0F0');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');
        $styleRed = new Style(fontColor: 'CC0000', fontBold: true);
        $styleBlue = new Style(fontColor: '0000CC');

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
        $writer->addRow($makeRow([
            'Cabang:',
            !empty($request->branch_codes)
                ? implode(', ', (array) $request->branch_codes)
                : 'Semua',
        ]));
        $writer->addRow($makeRow(['Customer:', $request->cust_from ? $request->cust_from . ' s/d ' . $request->cust_to : 'Semua']));
        $writer->addRow($makeRow(['Operator:', (auth('sysuser')->user() ?? auth()->user())->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        if ($request->input('report_type', 'detail') === 'rekap') {
            $writer->addRow($makeRow(['Customer', 'Qty. Sisa', 'Qty. Stok'], $styleHeader));

            $grandTotalQty = 0;
            foreach ($results as $row) {
                $grandTotalQty += (float) $row->fqty;
                $writer->addRow($makeRow([
                    $row->fcustno . ' - ' . $row->fcustomername,
                    (float) $row->fqty,
                    (float) $row->fstok,
                ]));
            }

            $writer->addRow($makeRow(['GRAND TOTAL QTY SO BELUM DIKIRIM', $grandTotalQty, ''], $styleGrandTotal));
            $writer->close();

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // Header Kolom
        $writer->addRow($makeRow([
            'No. SO',
            'Tanggal',
            'Nama Barang',
            'Satuan',
            '@ Harga',
            'Qty. Sisa',
            'Qty. Stok',
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
                '',
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
                    (float) $row->fstok,
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
                '',
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
            '',
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function excelProduct(Request $request)
    {
        $results = $this->getQueryResult($request, 'produk');

        $filename = 'SO_Belum_Dikirim_Produk_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup = new Style(fontBold: true, backgroundColor: 'FFE6E6');
        $styleSubtotal = new Style(fontBold: true, backgroundColor: 'FFF0F0');
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
        $writer->addRow($makeRow([
            'Cabang:',
            !empty($request->branch_codes)
                ? implode(', ', (array) $request->branch_codes)
                : 'Semua',
        ]));
        $writer->addRow($makeRow(['Customer:', $request->cust_from ? $request->cust_from . ' s/d ' . $request->cust_to : 'Semua']));
        $writer->addRow($makeRow(['Operator:', (auth('sysuser')->user() ?? auth()->user())->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        if ($request->input('report_type', 'detail') === 'rekap') {
            $writer->addRow($makeRow(['Kode Barang', 'Nama Barang', 'Satuan', 'Qty. Sisa', 'Qty. Stok'], $styleHeader));

            $grandTotalQty = 0;
            foreach ($results as $row) {
                $grandTotalQty += (float) $row->fqty;
                $writer->addRow($makeRow([
                    $row->fprdcode,
                    $row->fprdname,
                    $row->fsatuan,
                    (float) $row->fqty,
                    (float) $row->fstok,
                ]));
            }

            $writer->addRow($makeRow(['GRAND TOTAL QTY SO BELUM DIKIRIM', '', '', $grandTotalQty, ''], $styleGrandTotal));
            $writer->close();

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // Header Kolom
        $writer->addRow($makeRow([
            'No. SO',
            'Tanggal',
            'Nama Customer',
            'Satuan',
            '@ Harga',
            'Qty. Sisa',
            'Qty. Stok',
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
                '',
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
                    (float) $row->fstok,
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
                '',
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
            '',
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getQueryResult(Request $request, string $grouping)
    {
        $reportType = $request->input('report_type', 'detail');
        $remainQty = 'CASE WHEN d.fsatuan = p.fsatuanbesar AND COALESCE(p.fqtykecil, 0) > 0 THEN COALESCE(d.fqtyremain, 0) / p.fqtykecil ELSE COALESCE(d.fqtyremain, 0) END';
        $stockQty = "COALESCE(CAST(NULLIF(p.fstok, '') AS NUMERIC), 0)";

        $query = DB::table('trsomt as m')
            ->join('trsodt as d', 'm.fsono', '=', 'd.fsono')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->where('d.fqtyremain', '>', 0)
            ->where('m.fclose', '0');
        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (!empty($selectedBranches)) {
            $query->whereIn('m.fbranchcode', (array) $selectedBranches);
        }

        if ($request->date_from) {
            $query->where('m.fsodate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fsodate', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->cust_from && $request->cust_to) {
            $query->whereBetween('c.fcustomercode', [$request->cust_from, $request->cust_to]);
        }
        if ($request->group_prd) {
            $query->where('p.fgroupcode', $request->group_prd);
        }
        if ($request->selected_products) {
            $query->whereIn('p.fprdcode', explode(',', $request->selected_products));
        }
        if ($request->has('only_stok')) {
            $query->whereRaw($stockQty . ' > 0');
        }

        if ($reportType === 'rekap') {
            if ($grouping == 'produk') {
                return $query->select(
                    'p.fprdcode',
                    'p.fprdname',
                    DB::raw('MAX(d.fsatuan) as fsatuan'),
                    DB::raw("MAX($stockQty) as fstok"),
                    DB::raw("SUM($remainQty) as fqty")
                )
                    ->groupBy('p.fprdcode', 'p.fprdname')
                    ->orderBy('p.fprdcode')
                    ->get();
            }

            return $query->select(
                'm.fcustno',
                'c.fcustomername',
                DB::raw("SUM($remainQty) as fqty"),
                DB::raw("SUM($stockQty) as fstok")
            )
                ->groupBy('m.fcustno', 'c.fcustomername')
                ->orderBy('m.fcustno')
                ->get();
        }

        $query->select(
            'm.fsono',
            'm.fsodate',
            'm.fcustno',
            'c.fcustomername',
            'p.fprdcode',
            'p.fprdname',
            'd.fsatuan',
            'd.fpricenet',
            DB::raw($stockQty . ' as fstok'),
            'p.fqtykecil',
            'p.fsatuanbesar',
            DB::raw($remainQty . ' as fqty')
        );

        if ($grouping == 'produk') {
            return $query->orderBy('p.fprdcode')->orderBy('m.fsodate')->get()->groupBy('fprdcode');
        }

        return $query->orderBy('c.fcustomercode')->orderBy('m.fsono')->get()->groupBy('fcustomercode');
    }
}
