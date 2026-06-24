<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingSuratJalanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get(['fcabangkode', 'fcabangname']);
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingsuratjalan.index', compact('branches', 'customers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type = $request->input('display_type', 'detail');
        $groupedData = $results->groupBy('fstockmtno');

        return view('listingsuratjalan.print', [
            'groupedData' => $groupedData,
            'type' => $type,
            'request' => $request,
            'user_session' => auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $results = $this->buildQuery($request)->get();
        $type = $request->input('display_type', 'detail');
        $groupedData = $results->groupBy('fstockmtno');

        $filename = 'listing_surat_jalan_' . date('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Writer();
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontColor: 'C00000');
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');

        $makeRow = function (array $values, ?Style $style = null) use ($writer): void {
            $cells = array_map(fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value), $values);
            $writer->addRow(new Row($cells));
        };

        $makeRow(['LISTING SURAT JALAN'], $styleTitle);
        $makeRow(['Periode: ' . ($request->date_from ?? '...') . ' s/d ' . ($request->date_to ?? '...')]);
        $makeRow(['Cabang: ' . (is_array($request->branch_codes ?? null) ? implode(', ', $request->branch_codes) : 'Semua')]);
        $makeRow(['Customer: ' . ($request->customer ?: 'Semua')]);
        $makeRow([]);

        $header = ['Cab.', 'No.Transaksi', 'Tanggal', 'Gudang', 'Cust#', 'Nama Customer', 'Keterangan'];
        if ($type === 'detail') {
            $header = array_merge($header, ['Kode Barang', 'Nama Barang', 'No.Ref', 'Quantity']);
        }
        $makeRow($header, $styleHeader);

        foreach ($groupedData as $details) {
            $h = $details->first();
            $headerValues = [
                $h->fbranchcode,
                $h->fstockmtno,
                $h->fstockmtdate,
                $h->ffrom,
                $h->fsupplier,
                $h->fcustname,
                $h->fket,
            ];

            if ($type === 'detail') {
                foreach ($details as $detail) {
                    $makeRow(array_merge($headerValues, [
                        $detail->fprdcode,
                        $detail->fprdname,
                        $detail->frefdtno,
                        (float) $detail->fqty,
                    ]));
                }
            } else {
                $makeRow($headerValues);
            }
        }

        $writer->close();

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function buildQuery(Request $request)
    {
        $sumSrj = DB::table('trstockdt')
            ->select('fstockmtno', DB::raw('SUM(fqtykecil) AS fqtykecilsrj'))
            ->groupBy('fstockmtno');

        $inv = DB::table('trandt')
            ->select('frefsrj', 'fprdcode', 'fsono')
            ->where('frefcode', 'SRJ')
            ->groupBy('frefsrj', 'fprdcode', 'fsono');

        $rej = DB::table('trstockdt')
            ->select('frefdtno', 'fprdcode', DB::raw('SUM(fqtykecil) AS fqtyrtr'))
            ->where('fstockmtcode', 'REJ')
            ->where('fcode', 'S')
            ->groupBy('frefdtno', 'fprdcode');

        $expedisi = DB::table('tbmaster')
            ->select('fmastercode', DB::raw('fmastername AS fexpedisiname'))
            ->where('ftblcode', 'EXPEDISI');

        $query = DB::table('trstockmt as m')
            ->leftJoin('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('mscustomer as c', 'm.fsupplier', '=', 'c.fcustomercode')
            ->leftJoinSub($sumSrj, 'sum_srj', 'sum_srj.fstockmtno', '=', 'm.fstockmtno')
            ->leftJoinSub($inv, 'inv', function ($join) {
                $join->on('m.fstockmtno', '=', 'inv.frefsrj')
                    ->on('inv.fprdcode', '=', 'd.fprdcode')
                    ->on('d.frefdtno', '=', 'inv.fsono');
            })
            ->leftJoinSub($rej, 'rej', function ($join) {
                $join->on('m.fstockmtno', '=', 'rej.frefdtno')
                    ->on('rej.fprdcode', '=', 'd.fprdcode');
            })
            // Aktifkan kembali jika kolom t.fongkir atau x.fexpedisiname mau digunakan di selectRaw
            // ->leftJoin('trtimbanganmt as t', 'm.freftimbangan', '=', 't.ftrxno')
            // ->leftJoinSub($expedisi, 'x', 't.fexpedisi', '=', 'x.fmastercode')
            ->where('m.fstockmtcode', 'SRJ')

            // PERBAIKAN UTAMA: Tanda koma setelah d.frefdtno dihapus total sebelum penutupan quotes selectRaw
            ->selectRaw("
            m.fstockmtno,
            m.fstockmtdate,
            m.fbranchcode,
            m.fsupplier,
            c.fcustomername AS fcustname,
            c.faddress,
            m.frefno,
            m.ffrom,
            m.fket,
            m.fusercreate,
            d.fprdcode,
            p.fprdname,
            d.fqty,
            d.fsatuan,
            d.frefdtno,
            d.fqtykecil AS ftotalongkir
        ");

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branchCodes = array_values(array_filter((array) $request->input('branch_codes', [])));
        if ($branchCodes !== []) {
            $query->whereIn('m.fbranchcode', $branchCodes);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('m.fstockmtdate', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('m.fstockmtdate', '<=', $request->date_to);
        }

        if ($request->filled('customer')) {
            $query->where('m.fsupplier', $request->customer);
        }

        if ($request->boolean('belum_faktur')) {
            $query->whereRaw('COALESCE(d.fqty, 0) > (COALESCE(inv.fqtyinv, 0) * COALESCE(p.fqtykecil, 1)) + COALESCE(rej.fqtyrtr, 0)');
        }

        return $query->orderBy('m.fstockmtno')->orderBy('d.fprdcode');
    }
}
