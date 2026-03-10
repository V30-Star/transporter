<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

class ReportingSupplierController extends Controller
{
  public function index()
  {
    $suppliers = DB::table('public.mssupplier')
      ->orderBy('fsuppliercode', 'asc')
      ->get();

    return view('reportingsupplier.index', compact('suppliers'));
  }

  public function print(Request $request)
  {
    $data      = $this->getData($request);
    $printDate = Carbon::now()->format('d/m/Y H:i');

    return view('reportingsupplier.print', compact('data', 'printDate'));
  }

  public function exportExcel(Request $request)
  {
    $data = $this->getData($request);

    $filename = "Master_Supplier_" . date('YmdHis') . ".xlsx";
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

    $writer = new Writer();
    $writer->openToFile($tempFile);

    // --- Styles ---
    $styleTitle      = new Style(fontBold: true, fontSize: 14);
    $styleHeader     = new Style(fontBold: true, backgroundColor: 'D3D3D3');
    $styleRow        = new Style(fontBold: false);
    $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

    $makeRow = function (array $values, ?Style $style = null): Row {
      $cells = array_map(
        fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
        $values
      );
      return new Row($cells);
    };

    // --- Header Informasi ---
    $writer->addRow($makeRow(['LIST OF MASTER SUPPLIER'], $styleTitle));
    $writer->addRow($makeRow(['Tanggal:', date('d/m/Y') . '  Jam: ' . date('H:i')]));
    $writer->addRow($makeRow([
      'Supplier:',
      $request->supplier_from
        ? '[' . $request->supplier_from . '] s/d [' . $request->supplier_to . ']'
        : 'Semua'
    ]));
    $writer->addRow($makeRow([]));

    // --- Header Kolom ---
    $writer->addRow($makeRow([
      'No',
      'Supplier#',
      'Nama Supplier',
      'Alamat',
      'Telp',
      'Kontak Person',
      'Email',
      'Tempo (Hari)',
      'NPWP',
    ], $styleHeader));

    foreach ($data as $i => $row) {
      $writer->addRow($makeRow([
        $i + 1,
        $row->fsuppliercode,
        $row->fsuppliername,
        $row->faddress ?? '',
        $row->ftelp ?? '',
        $row->fkontakperson ?? '',
        $row->femail ?? '',
        (int) ($row->ftempo ?? 0),
        $row->fnpwp ?? '',
      ], $styleRow));
    }

    // --- Grand Total ---
    $writer->addRow($makeRow([]));
    $writer->addRow($makeRow([
      'TOTAL KESELURUHAN DATA SUPPLIER',
      count($data) . ' Records',
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
    $query = DB::table('public.mssupplier');

    if ($request->supplier_from && $request->supplier_to) {
      $query->whereBetween('fsuppliercode', [
        $request->supplier_from,
        $request->supplier_to
      ]);
    }

    return $query->orderBy('fsuppliercode', 'asc')->get();
  }
}
