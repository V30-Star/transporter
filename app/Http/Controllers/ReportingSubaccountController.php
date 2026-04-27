<?php

namespace App\Http\Controllers;

use App\Models\Subaccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingSubaccountController extends Controller
{
    public function index()
    {
        $subAccounts = Subaccount::orderBy('fsubaccountcode', 'asc')->get();

        return view('reportingsubaccount.index', compact('subAccounts'));
    }

    public function print(Request $request)
    {
        $data = $this->getData($request);
        $printDate = Carbon::now()->format('d/m/Y H:i');

        return view('reportingsubaccount.print', compact('data', 'printDate'));
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getData($request);

        $filename = 'Master_SubAccount_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        // --- Styles ---
        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleActive = new Style(fontBold: false);
        $styleInactive = new Style(fontColor: '999999');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );

            return new Row($cells);
        };

        // --- Header Informasi ---
        $writer->addRow($makeRow(['MASTER SUB ACCOUNT REPORT'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow([
            'Range Kode:',
            $request->subaccount_from
              ? '['.$request->subaccount_from.'] s/d ['.$request->subaccount_to.']'
              : 'Semua',
        ]));
        $writer->addRow($makeRow([]));

        // --- Header Kolom ---
        $writer->addRow($makeRow([
            'No',
            'Code',
            'Sub Account Name',
            'Status',
            'Created By',
            'Created At',
        ], $styleHeader));

        foreach ($data as $i => $row) {
            $isActive = $row->fnonactive == 'N';
            $style = $isActive ? $styleActive : $styleInactive;

            $writer->addRow($makeRow([
                $i + 1,
                $row->fsubaccountcode,
                $row->fsubaccountname,
                $isActive ? 'Active' : 'Inactive',
                $row->fcreatedby ?? '',
                $row->fcreatedat
                  ? Carbon::parse($row->fcreatedat)->format('d/m/Y')
                  : '-',
            ], $style));
        }

        // --- Grand Total ---
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'TOTAL RECORD SUB ACCOUNT',
            count($data).' Records',
            '',
            '',
            '',
            '',
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getData(Request $request)
    {
        $query = Subaccount::query();

        if ($request->subaccount_from && $request->subaccount_to) {
            $query->whereBetween('fsubaccountcode', [
                $request->subaccount_from,
                $request->subaccount_to,
            ]);
        }

        return $query->orderBy('fsubaccountcode', 'asc')->get();
    }
}
