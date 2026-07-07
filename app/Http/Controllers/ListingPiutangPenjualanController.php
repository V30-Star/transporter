<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingPiutangPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $salesmen = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $wilayahs = DB::table('mswilayah')->orderBy('fwilayahcode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingpiutangpenjualan.index', compact(
            'branches', 'salesmen', 'wilayahs', 'customers', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $rows = $this->getRawData($request);

        return view('listingpiutangpenjualan.print', [
            'rows' => $rows,
            'mode' => $request->input('report_mode', 'detail') === 'rekap' ? 'rekap' : 'detail',
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', now()->toDateString());
        $tglPembayaran = $request->input('tgl_pembayaran_date', $perTanggal);
        $tglJatuhTempo = $request->input('due_date', $perTanggal);

        $base = DB::table('tranmt as m')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->selectRaw("m.fbranchcode, m.fsono, m.ftrcode AS fstockmtcode, m.fsodate, m.fjatuhtempo, m.frefno, m.fcustno AS fcustomer, c.fcustomername AS fcustname, m.famountso AS fnilainota, CASE WHEN m.ftrcode = 'REJ' THEN m.famountso * -1 ELSE m.famountso END AS famountso, m.fuserid, m.fsalesman, c.fwilayah")
            ->whereIn('m.ftrcode', ['INV', 'REJ'])
            ->where('m.fsodate', '<=', $perTanggal)
            ->where(function ($q) {
                $q->whereNull('m.ftunai')->orWhere('m.ftunai', '0')->orWhere('m.ftunai', '');
            });

        $this->applyBranchVisibilityScope($base, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $base->whereIn('m.fbranchcode', (array) $branches);
        }
        if ($request->filled('salesman')) {
            $base->where('m.fsalesman', $request->input('salesman'));
        }
        if ($request->filled('wilayah')) {
            $base->where('c.fwilayah', $request->input('wilayah'));
        }
        if ($request->filled('cust_from') && $request->filled('cust_to')) {
            $base->whereBetween('m.fcustno', [$request->input('cust_from'), $request->input('cust_to')]);
        }
        if ($request->input('due_filter') === 'due') {
            $base->where('m.fjatuhtempo', '<=', $tglJatuhTempo);
        }

        $payments = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->selectRaw('d.frefno, SUM(COALESCE(d.fkasdtvalue, 0) + COALESCE(d.fdiscount, 0)) AS ftotalbayar')
            ->where('m.ftrancode', 'RCP')
            ->groupBy('d.frefno');

        $journals = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->selectRaw('d.frefno, SUM(d.famount) AS ftotalsju')
            ->where('d.faccount', '11130.01')
            ->where('d.fdk', 'K')
            ->groupBy('d.frefno');

        if ($request->boolean('tgl_pembayaran')) {
            $payments->where('m.fkasmtdate', '<=', $tglPembayaran);
            $journals->where('m.fjurnaldate', '<=', $tglPembayaran);
        }

        return DB::query()
            ->fromSub($base, 'a')
            ->leftJoinSub($payments, 'b', 'a.fsono', '=', 'b.frefno')
            ->leftJoinSub($journals, 'c', 'a.fsono', '=', 'c.frefno')
            ->selectRaw("a.fbranchcode, a.fsono, a.fstockmtcode, a.fsodate, a.fjatuhtempo, a.frefno, a.fcustomer, a.fcustname, a.fnilainota, a.famountso, a.fuserid, a.fsalesman, COALESCE(b.ftotalbayar, 0) AS ftotalbayar, COALESCE(c.ftotalsju, 0) AS ftotalsju, CASE WHEN a.fstockmtcode = 'REJ' THEN (a.fnilainota - (COALESCE(b.ftotalbayar, 0) + COALESCE(c.ftotalsju, 0))) * -1 ELSE a.fnilainota - (COALESCE(b.ftotalbayar, 0) + COALESCE(c.ftotalsju, 0)) END AS fsisapiu")
            ->whereRaw("(a.fnilainota - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0))) > 0")
            ->orderBy('a.fcustomer')
            ->orderBy('a.fsono')
            ->get();
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->getRawData($request);
        $groupedData = $rows->groupBy('fcustomer');
        $mode = $request->input('report_mode', 'detail') === 'rekap' ? 'rekap' : 'detail';

        $filename = 'Listing_Piutang_Penjualan_'.date('YmdHis').'.xlsx';
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
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['LISTING PIUTANG PENJUALAN'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Per Tanggal:', $request->input('per_tanggal', date('Y-m-d'))]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No.', 'Cab.', 'No. Faktur', 'Tanggal', 'Jatuh Tempo', 'Nilai Faktur', 'Nilai Piutang', 'Salesman'
        ], $styleHeader));

        $grandFaktur = 0;
        $grandPiutang = 0;
        $idx = 0;

        foreach ($groupedData as $customer => $items) {
            $first = $items->first();
            $customerFaktur = $items->sum('famountso');
            $customerPiutang = $items->sum('fsisapiu');
            $grandFaktur += $customerFaktur;
            $grandPiutang += $customerPiutang;

            // Group Row
            $writer->addRow($makeRow([
                'Customer: '.$customer.' - '.$first->fcustname, '', '', '', '', '', '', ''
            ], $styleGroup));

            if ($mode === 'detail') {
                foreach ($items as $row) {
                    $idx++;
                    $writer->addRow($makeRow([
                        $idx,
                        $row->fbranchcode,
                        $row->fsono,
                        $row->fsodate ? date('d/m/Y', strtotime($row->fsodate)) : '',
                        $row->fjatuhtempo ? date('d/m/Y', strtotime($row->fjatuhtempo)) : '',
                        (float) $row->famountso,
                        (float) $row->fsisapiu,
                        $row->fsalesman
                    ]));
                }

                // Subtotal Row
                $writer->addRow($makeRow([
                    'Subtotal '.$first->fcustname, '', '', '', '',
                    (float) $customerFaktur,
                    (float) $customerPiutang,
                    ''
                ], $styleSubtotal));
            } else {
                // Rekap Mode shows only customer subtotal
                $writer->addRow($makeRow([
                    'Subtotal '.$first->fcustname, '', '', '', '',
                    (float) $customerFaktur,
                    (float) $customerPiutang,
                    ''
                ], $styleSubtotal));
            }
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL', '', '', '', '',
            (float) $grandFaktur,
            (float) $grandPiutang,
            ''
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
