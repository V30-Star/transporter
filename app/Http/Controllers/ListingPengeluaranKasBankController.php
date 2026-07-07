<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingPengeluaranKasBankController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $accounts = DB::table('account')->orderBy('faccount')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingpengeluarankasbank.index', compact(
            'branches', 'accounts', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);

        return view('listingpengeluarankasbank.print', [
            'results' => $results,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $query = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->leftJoin('account as h', 'm.faccountno', '=', 'h.faccount')
            ->leftJoin('account as a', 'd.faccount', '=', 'a.faccount')
            ->where('m.ftrancode', 'BKK')
            ->select(
                'm.fkasmtid',
                'm.fkasmtno',
                'm.fkasmtdate',
                'm.fwhom',
                'h.faccname as accounth',
                'm.fket',
                DB::raw('COALESCE(m.famountpay, 0) as famountpay'),
                'm.fuserid',
                'a.faccname as accountd',
                'd.fnote',
                DB::raw('COALESCE(d.fkasdtvalue, 0) as fkasdtvalue'),
                'd.faccount',
                'm.faccountno',
                'm.ftrancode'
            );

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $query->whereIn('m.fbranchcode', (array) $branches);
        }

        if ($request->date_from) {
            $query->where('m.fkasmtdate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fkasmtdate', '<=', $request->date_to);
        }
        if ($request->account_no) {
            $query->where('m.faccountno', $request->account_no);
        }
        if ($request->boolean('giro_mundur')) {
            $query->where('m.fgiromundur', '1');
        }

        return $query
            ->orderBy('m.fkasmtdate', 'asc')
            ->orderBy('m.fkasmtno', 'asc')
            ->orderBy('d.faccount', 'asc')
            ->get();
    }

    public function exportExcel(Request $request)
    {
        $results = $this->getRawData($request);
        $grouped = $results->groupBy('fkasmtid');

        $filename = 'Listing_Pengeluaran_Kas_Bank_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleSubgroup = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['LISTING PENGELUARAN KAS/BANK'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No. Voucher', 'Tanggal', 'Nama Account (K)', 'Keterangan', 'Total Bayar', 'User-Id',
            'Account# (D)', 'Nama Account (D)', 'Uraian', 'Nilai Bayar'
        ], $styleHeader));

        $grandTotal = 0;

        foreach ($grouped as $items) {
            $h = $items->first();

            // Header data row
            $writer->addRow($makeRow([
                $h->fkasmtno,
                $h->fkasmtdate ? date('d/m/Y', strtotime($h->fkasmtdate)) : '',
                $h->accounth,
                $h->fket,
                (float) $h->famountpay,
                trim($h->fuserid),
                '', '', '', ''
            ], $styleSubgroup));

            foreach ($items as $d) {
                $grandTotal += (float) $d->fkasdtvalue;
                // Detail row
                $writer->addRow($makeRow([
                    '', '', '', '', '', '',
                    $d->faccount,
                    $d->accountd,
                    $d->fnote,
                    (float) $d->fkasdtvalue
                ]));
            }
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'TOTAL PENGELUARAN KAS/BANK', '', '', '', '', '', '', '', '',
            (float) $grandTotal
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
