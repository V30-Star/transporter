<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingSOController extends Controller
{
    public function index()
    {
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get();
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $groups = DB::table('ms_groupprd')->orderBy('fgroupcode')->get();
        $mereks = DB::table('msmerek')->orderBy('fmerekcode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingso.index', compact('customers', 'products', 'branches', 'groups', 'mereks', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $query = DB::table('public.trsomt as mt')
            ->leftJoin('public.mscustomer as cust', 'mt.fcustno', '=', 'cust.fcustomercode')
            ->leftJoin('public.mssalesman as sls', 'mt.fsalesman', '=', 'sls.fsalesmancode')
            ->select('mt.*', 'cust.fcustomercode', 'cust.fcustomername', 'sls.fsalesmanname');
        $this->applyBranchVisibilityScope($query, 'mt.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (!empty($selectedBranches)) {
            $query->whereIn('mt.fbranchcode', (array) $selectedBranches);
        }

        if ($request->date_from) {
            $query->where('mt.fsodate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('mt.fsodate', '<=', $request->date_to.' 23:59:59');
        }

        if ($request->customer_code) {
            $query->where('cust.fcustomercode', $request->customer_code);
        } elseif ($request->cust_from && $request->cust_to) {
            $query->whereBetween('cust.fcustomercode', [$request->cust_from, $request->cust_to]);
        }

        $selectedProducts = collect(explode(',', (string) $request->selected_products))
            ->map(fn ($code) => trim($code))
            ->filter()
            ->values()
            ->all();

        if (!empty($selectedProducts)) {
            $query->whereExists(function ($q) use ($selectedProducts) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->whereIn('dt.fprdcode', $selectedProducts);
            });
        } elseif ($request->product_code) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->where('dt.fprdcode', $request->product_code);
            });
        } elseif ($request->prd_from && $request->prd_to) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->whereBetween('dt.fprdcode', [$request->prd_from, $request->prd_to]);
            });
        }

        if ($request->group_code) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->join('public.msprd as p', 'dt.fprdcode', '=', 'p.fprdcode')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->where('p.fgroupcode', $request->group_code);
            });
        }

        if ($request->merek_code) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->join('public.msprd as p', 'dt.fprdcode', '=', 'p.fprdcode')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->where('p.fmerek', $request->merek_code);
            });
        }

        if ($request->input('so_filter') === 'only_pending' || (! $request->has('all_so') && $request->has('only_pending'))) {
            $query->where('mt.fclose', '0');
        }

        $results = $query->orderBy('mt.fsodate', 'asc')->orderBy('mt.fsono', 'asc')->get();

        $totalFaktur = 0;
        foreach ($results as $row) {
            $row->details = DB::table('public.trsodt as dt')
                ->leftJoin('public.msprd as p', 'dt.fprdcode', '=', 'p.fprdcode')
                ->select('dt.*', 'p.fprdname as product_name')
                ->where('dt.fsono', $row->fsono)
                ->get();
            $totalFaktur += (float) $row->famountso;
        }

        return view('listingso.print', [
            'soData' => $results,
            'totalFaktur' => $totalFaktur,
            'user_session' => auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $query = DB::table('public.trsomt as mt')
            ->leftJoin('public.mscustomer as cust', 'mt.fcustno', '=', 'cust.fcustomercode')
            ->leftJoin('public.mssalesman as sls', 'mt.fsalesman', '=', 'sls.fsalesmancode')
            ->select('mt.*', 'cust.fcustomercode', 'cust.fcustomername', 'sls.fsalesmanname');
        $this->applyBranchVisibilityScope($query, 'mt.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (!empty($selectedBranches)) {
            $query->whereIn('mt.fbranchcode', (array) $selectedBranches);
        }

        if ($request->date_from) {
            $query->where('mt.fsodate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('mt.fsodate', '<=', $request->date_to.' 23:59:59');
        }

        if ($request->customer_code) {
            $query->where('cust.fcustomercode', $request->customer_code);
        } elseif ($request->cust_from && $request->cust_to) {
            $query->whereBetween('cust.fcustomercode', [$request->cust_from, $request->cust_to]);
        }

        $selectedProducts = collect(explode(',', (string) $request->selected_products))
            ->map(fn ($code) => trim($code))
            ->filter()
            ->values()
            ->all();

        if (!empty($selectedProducts)) {
            $query->whereExists(function ($q) use ($selectedProducts) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->whereIn('dt.fprdcode', $selectedProducts);
            });
        } elseif ($request->product_code) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->where('dt.fprdcode', $request->product_code);
            });
        } elseif ($request->prd_from && $request->prd_to) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->whereBetween('dt.fprdcode', [$request->prd_from, $request->prd_to]);
            });
        }

        if ($request->group_code) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->join('public.msprd as p', 'dt.fprdcode', '=', 'p.fprdcode')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->where('p.fgroupcode', $request->group_code);
            });
        }

        if ($request->merek_code) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->join('public.msprd as p', 'dt.fprdcode', '=', 'p.fprdcode')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->where('p.fmerek', $request->merek_code);
            });
        }

        if ($request->input('so_filter') === 'only_pending' || (! $request->has('all_so') && $request->has('only_pending'))) {
            $query->where('mt.fclose', '0');
        }

        $results = $query->orderBy('mt.fsodate', 'asc')->orderBy('mt.fsono', 'asc')->get();

        foreach ($results as $row) {
            $row->details = DB::table('public.trsodt as dt')->where('dt.fsono', $row->fsono)->get();
        }

        $filename = 'Listing_SO_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        // --- Styles ---
        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleMaster = new Style(fontBold: true);
        $styleDetail = new Style(fontColor: '0000FF'); // biru untuk detail
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );

            return new Row($cells);
        };

        // --- Header Informasi ---
        $writer->addRow($makeRow(['LISTING SALES ORDER'], $styleTitle));
        $writer->addRow($makeRow([
            'Customer:',
            $request->customer_code
                ? $request->customer_code
                : ($request->cust_from
                    ? '['.$request->cust_from.'] s/d ['.$request->cust_to.']'
                    : 'Semua'),
        ]));
        $writer->addRow($makeRow([
            'Periode:',
            ($request->date_from ?? '...').' s/d '.($request->date_to ?? '...'),
        ]));
        $writer->addRow($makeRow([
            'Cabang:',
            !empty($request->branch_codes)
                ? implode(', ', (array) $request->branch_codes)
                : 'Semua',
        ]));
        $writer->addRow($makeRow([]));

        // --- Header Kolom ---
        $writer->addRow($makeRow([
            'No. Transaksi',
            'Tanggal',
            'Nama Customer',
            'Salesman',
            'Total Harga',
            'Disc',
            'PPN',
            'Total SO',
            'Close?',
            'Kode Barang',
            'Nama Barang',
            'Quantity',
            'Qty Sisa',
            '@ Harga',
            'Total Harga Detail',
        ], $styleHeader));

        $totalFaktur = 0;

        foreach ($results as $mt) {
            $totalFaktur += (float) $mt->famountso;
            $isFirst = true;

            foreach ($mt->details as $dt) {
                $writer->addRow($makeRow([
                    $isFirst ? $mt->fsono : '',
                    $isFirst ? Carbon::parse($mt->fsodate)->format('d/m/Y') : '',
                    $isFirst ? $mt->fcustomername : '',
                    $isFirst ? ($mt->fsalesmanname ?? '') : '',
                    $isFirst ? (float) $mt->famountgross : '',
                    $isFirst ? (float) $mt->fdiscount : '',
                    $isFirst ? (float) $mt->famountpajak : '',
                    $isFirst ? (float) $mt->famountso : '',
                    $isFirst ? ($mt->fclose == '1' ? 'Y' : 'N') : '',
                    $dt->fitemno,
                    $dt->fitemdesc,
                    (float) $dt->fqty,
                    0.00,
                    (float) $dt->fprice,
                    (float) ($dt->fqty * $dt->fprice),
                ], $isFirst ? $styleMaster : $styleDetail));

                $isFirst = false;
            }
        }

        // --- Grand Total ---
        $writer->addRow($makeRow([
            'GRAND TOTAL LISTING SALES ORDER',
            '',
            '',
            '',
            '',
            '',
            '',
            (float) $totalFaktur,
            '',
            '',
            '',
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
}
