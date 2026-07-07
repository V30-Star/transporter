<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingFakturPajakPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get(['fcabangkode', 'fcabangname']);
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingfakturpajakpenjualan.index', compact('branches', 'customers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $results = $this->buildQuery($request)->get();

        return view('listingfakturpajakpenjualan.print', [
            'results' => $results,
            'request' => $request,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function buildQuery(Request $request)
    {
        $query = DB::table('tranmt as m')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->selectRaw("\n                m.fkodefp,\n                
            m.ftaxno,\n                m.fsono,\n                
            m.fsodate,\n         c.fnpwp,\n                m.fcustno,\n                
            c.fcustomername as fcustname,\n                m.ftotalsalesnet AS famountgross,\n                
            CASE\n                    WHEN m.fincludeppn = '1' THEN (100 / (100 + m.fppnpersen)) * m.fdiscount\n                    
            ELSE m.fdiscount\n                END AS fdiscount,\n                m.ftotalsalesnet - (\n                    
            CASE\n                        WHEN m.fincludeppn = '1' THEN (100 / (100 + m.fppnpersen)) * m.fdiscount\n                        
            ELSE m.fdiscount\n                    END\n                ) AS famountsonet,\n                m.famountpajak\n            ")
            ->where('m.famountpajak', '>', 0);

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branchCodes = array_values(array_filter((array) $request->input('branch_codes', [])));
        if ($branchCodes !== []) {
            $query->whereIn('m.fbranchcode', $branchCodes);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('m.fsodate', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('m.fsodate', '<=', $request->date_to);
        }

        if ($request->filled('customer')) {
            $query->where('m.fcustno', $request->customer);
        }

        return $query->orderBy('m.ftaxno');
    }

    public function exportExcel(Request $request)
    {
        $results = $this->buildQuery($request)->get();

        $filename = 'Listing_Faktur_Pajak_Penjualan_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['LISTING FAKTUR PAJAK PENJUALAN'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'Faktur Pajak', 'No. Faktur', 'Tanggal', 'Nama Customer', 'NPWP', 'Harga Jual', 'Discount', 'DPP', 'PPN'
        ], $styleHeader));

        $totalHarga = 0;
        $totalDiscount = 0;
        $totalDpp = 0;
        $totalPpn = 0;

        foreach ($results as $row) {
            $hargaJual = (float) $row->famountgross;
            $discount = (float) $row->fdiscount;
            $dpp = (float) $row->famountsonet;
            $ppn = (float) $row->famountpajak;

            $totalHarga += $hargaJual;
            $totalDiscount += $discount;
            $totalDpp += $dpp;
            $totalPpn += $ppn;

            $writer->addRow($makeRow([
                $row->fkodefp . $row->ftaxno,
                $row->fsono,
                $row->fsodate ? date('d/m/Y', strtotime($row->fsodate)) : '',
                $row->fcustname,
                $row->fnpwp,
                $hargaJual,
                $discount,
                $dpp,
                $ppn
            ]));
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL', '', '', '', '',
            $totalHarga,
            $totalDiscount,
            $totalDpp,
            $totalPpn
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
