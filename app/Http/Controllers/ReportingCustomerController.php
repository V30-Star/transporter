<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

class ReportingCustomerController extends Controller
{
    public function index()
    {
        $customers = DB::table('mscustomer')->orderBy('fcustomercode', 'asc')->get();
        $salesmen  = DB::table('mssalesman')->where('fnonactive', '0')->orderBy('fsalesmanname', 'asc')->get();

        return view('reportingcustomer.index', compact('customers', 'salesmen'));
    }

    public function print(Request $request)
    {
        $data      = $this->getData($request);
        $printDate = Carbon::now()->format('d/m/Y H:i');

        return view('reportingcustomer.print', compact('data', 'printDate'));
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getData($request);

        $filename = "Master_Customer_" . date('YmdHis') . ".xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer();
        $writer->openToFile($tempFile);

        // --- Styles ---
        $styleTitle      = new Style(fontBold: true, fontSize: 14);
        $styleHeader     = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleRow        = new Style(fontBold: false);
        $styleBlokir     = new Style(fontColor: 'CC0000'); // merah untuk customer blokir
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // --- Header Informasi ---
        $writer->addRow($makeRow(['LIST OF MASTER CUSTOMER'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y') . '  Jam: ' . date('H:i')]));
        $writer->addRow($makeRow([
            'Customer:',
            $request->cust_from
                ? '[' . $request->cust_from . '] s/d [' . $request->cust_to . ']'
                : 'Semua'
        ]));
        $writer->addRow($makeRow([
            'Salesman:',
            $request->salesman ?? 'Semua'
        ]));
        $writer->addRow($makeRow([
            'Min. Limit:',
            $request->limit > 0 ? number_format($request->limit, 0, ',', '.') : 'Semua'
        ]));
        $writer->addRow($makeRow([
            'Filter Blokir:',
            $request->has('is_blocked') ? 'Hanya Yang Diblokir' : 'Semua'
        ]));
        $writer->addRow($makeRow([]));

        // --- Header Kolom ---
        $writer->addRow($makeRow([
            'No',
            'Cust#',
            'Nama Customer',
            'Alamat',
            'Telp',
            'Salesman',
            'Kontak Person',
            'Email',
            'Limit',
            'Tempo (Hr)',
            'Blokir',
        ], $styleHeader));

        foreach ($data as $i => $row) {
            $isBlokir = $row->fblokir == '1';
            $style    = $isBlokir ? $styleBlokir : $styleRow;

            $writer->addRow($makeRow([
                $i + 1,
                $row->fcustomercode,
                $row->fcustomername,
                $row->faddress ?? '',
                $row->ftelp ?? '',
                $row->fsalesmanname ?? $row->fsalesman ?? '',
                $row->fkontakperson ?? '',
                $row->femail ?? '',
                (float) $row->flimit,
                (int) $row->ftempo,
                $isBlokir ? 'Blokir' : 'Aktif',
            ], $style));
        }

        // --- Grand Total ---
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'TOTAL KESELURUHAN DATA CUSTOMER',
            count($data) . ' Records',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getData(Request $request)
    {
        $query = DB::table('mscustomer as c')
            ->leftJoin('mssalesman as s', 'c.fsalesman', '=', 's.fsalesmancode')
            ->select('c.*', 's.fsalesmanname');

        if ($request->cust_from && $request->cust_to) {
            $query->whereBetween('c.fcustomercode', [$request->cust_from, $request->cust_to]);
        }

        if ($request->salesman) {
            $query->where('c.fsalesman', $request->salesman);
        }

        if ($request->limit > 0) {
            $query->where('c.flimit', '>=', $request->limit);
        }

        if ($request->has('is_blocked')) {
            $query->where('c.fblokir', '1');
        }

        return $query->orderBy('c.fcustomercode', 'asc')->get();
    }
}
