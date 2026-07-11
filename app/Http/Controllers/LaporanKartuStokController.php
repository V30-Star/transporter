<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class LaporanKartuStokController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get(['fcabangkode', 'fcabangname']);
        $warehouses = DB::table('mswh')->where('fnonactive', '0')->orderBy('fwhcode')->get(['fwhcode', 'fwhname', 'fbranchcode']);
        $groups = DB::table('ms_groupprd')->orderBy('fgroupcode')->get(['fgroupcode', 'fgroupname']);
        $mereks = DB::table('msmerek')->orderBy('fmerekcode')->get(['fmerekcode', 'fmerekname']);
        $products = DB::table('msprd')->where('ftype', 'Produk')->orderBy('fprdcode')->get(['fprdcode', 'fprdname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('laporankartustok.index', compact('branches', 'warehouses', 'groups', 'mereks', 'products', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $mode = $request->input('report_mode', 'rekap') === 'detail' ? 'detail' : 'rekap';

        return view('laporankartustok.print', [
            'rows' => $mode === 'detail' ? $this->detailRows($request) : $this->rekapRows($request),
            'mode' => $mode,
            'request' => $request,
            'title' => 'Laporan Kartu Stok',
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    /**
     * REKAP — versi lama: PHP menarik semua produk + semua transaksi in/out
     * lalu menjumlahkan manual di array $card (boros memori).
     * Versi baru: SUM & GROUP BY dilakukan di SQL, PHP cuma nampung
     * hasil akhir yang sudah teragregasi per produk (jauh lebih kecil).
     */
    private function rekapRows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfYear()->toDateString());
        $warehouses = $this->warehouses($request);

        $rows = collect();

        foreach ($warehouses as $wh) {
            // cursor() -> baris diambil satu-satu dari DB, tidak sekaligus
            // load semua ke Collection besar sebelum diproses.
            foreach ($this->rekapAggregatedQuery($wh->fwhcode, $request, $dateFrom, $dateTo)->cursor() as $row) {
                $row->qtysaldokecil = (float) $row->qtyawalkecil + (float) $row->qtymasukkecil - (float) $row->qtykeluarkecil;
                if ($this->matchStatus($row, $request)) {
                    $rows->push($row);
                }
            }
        }

        return $rows->sortBy([
            ['fwhcode', 'asc'],
            ['fgroupname', 'asc'],
            ['fprdcode', 'asc'],
        ])
            ->values();
    }

    /**
     * Query tunggal per gudang: saldo awal + masuk + keluar
     * sudah dihitung SUM di level SQL lewat subquery, bukan PHP array.
     */
    private function rekapAggregatedQuery(string $whcode, Request $request, string $dateFrom, string $dateTo)
    {
        $openingIn = $this->movementTotalSubquery($whcode, $request, null, $dateFrom, 'in');
        $openingOut = $this->movementTotalSubquery($whcode, $request, null, $dateFrom, 'out');
        $periodIn = $this->movementTotalSubquery($whcode, $request, $dateFrom, $dateTo, 'in');
        $periodOut = $this->movementTotalSubquery($whcode, $request, $dateFrom, $dateTo, 'out');

        $query = DB::table('msprd as p')
            ->leftJoin('prdwh as w', function ($join) use ($whcode) {
                $join->on('p.fprdcode', '=', 'w.fprdcode')->where('w.fwhcode', $whcode);
            })
            ->leftJoin('ms_groupprd as g', 'p.fgroupcode', '=', 'g.fgroupcode')
            ->leftJoin('msmerek as mr', 'p.fmerek', '=', 'mr.fmerekcode')
            ->leftJoinSub($openingIn, 'oi', 'oi.fprdcode', '=', 'p.fprdcode')
            ->leftJoinSub($openingOut, 'oo', 'oo.fprdcode', '=', 'p.fprdcode')
            ->leftJoinSub($periodIn, 'pi', 'pi.fprdcode', '=', 'p.fprdcode')
            ->leftJoinSub($periodOut, 'po', 'po.fprdcode', '=', 'p.fprdcode')
            ->where('p.ftype', 'Produk');

        $this->applyProductFilters($query, $request, 'p');

        return $query->selectRaw("
                ? as fwhcode,
                p.fprdcode, p.fprdname, p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2,
                COALESCE(CAST(NULLIF(p.fqtykecil::text,'') AS NUMERIC), 1) as qtykecil,
                p.fgroupcode, COALESCE(g.fgroupname, p.fgroupcode) as fgroupname,
                p.fmerek, COALESCE(mr.fmerekname, p.fmerek) as fmerekname,
                COALESCE(CAST(NULLIF(p.fminstock::text,'') AS NUMERIC),0) * COALESCE(CAST(NULLIF(p.fqtykecil::text,'') AS NUMERIC),1) as fminstock,
                COALESCE(CAST(NULLIF(w.fawal::text,'') AS NUMERIC),0)
                    + COALESCE(oi.qty, 0) - COALESCE(oo.qty, 0) as qtyawalkecil,
                COALESCE(pi.qty, 0) as qtymasukkecil,
                COALESCE(po.qty, 0) as qtykeluarkecil,
                COALESCE(p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2) as fsatuan
            ", [$whcode]);
    }

    private function movementTotalSubquery(string $whcode, Request $request, ?string $dateFrom, string $dateTo, string $direction)
    {
        $query = $this->movementBaseQuery($whcode, $request, $direction)
            ->selectRaw('d.fprdcode, SUM(COALESCE(d.fqtykecil, d.fqty, 0)) as qty')
            ->groupBy('d.fprdcode');

        if ($dateFrom) {
            $query->where('m.fstockmtdate', '>=', $dateFrom)
                ->where('m.fstockmtdate', '<=', $dateTo . ' 23:59:59');
        } else {
            $query->where('m.fstockmtdate', '<', $dateTo);
        }

        return $query;
    }

    /**
     * DETAIL — versi lama: get()->each() menarik semua baris in & out
     * jadi Collection besar sekaligus, lalu sortBy() di PHP untuk seluruh dataset.
     * Versi baru: UNION ALL + ORDER BY dilakukan di SQL (pakai index DB),
     * lalu di-cursor() per baris, saldo berjalan dihitung sambil jalan.
     */
    private function detailRows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfYear()->toDateString());
        $warehouses = $this->warehouses($request);

        $rows = collect();

        foreach ($warehouses as $wh) {
            // Saldo awal per produk (hasil sudah teragregasi dari query rekap)
            $openingBalances = $this->rekapAggregatedQuery($wh->fwhcode, $request, '', $dateFrom)
                ->get()
                ->keyBy(fn($r) => trim($r->fprdcode));

            $currentPrd = null;
            $running = 0.0;

            foreach ($this->detailUnionQuery($wh->fwhcode, $request, $dateFrom, $dateTo)->cursor() as $row) {
                $prdKey = trim($row->fprdcode);

                if ($prdKey !== $currentPrd) {
                    $currentPrd = $prdKey;
                    $opening = $openingBalances->get($prdKey);
                    $running = $opening ? (float) $opening->qtyawalkecil : 0.0;

                    if ($running != 0.0) {
                        $rows->push((object) [
                            'fwhcode' => $wh->fwhcode,
                            'fprdcode' => $prdKey,
                            'fprdname' => trim((string) $row->fprdname),
                            'fstockmt' => 'Saldo Awal',
                            'fstockmtcode' => '',
                            'fstockdate' => null,
                            'frefno' => '',
                            'fsuppliername' => '',
                            'fsatuan' => $row->fsatuan,
                            'qtyawalkecil' => $running,
                            'qtymasukkecil' => 0.0,
                            'qtykeluarkecil' => 0.0,
                            'qtysaldokecil' => $running,
                            'fminstock' => $opening->fminstock ?? 0.0,
                        ]);
                    }
                }

                $masuk = (float) $row->qtymasukkecil;
                $keluar = (float) $row->qtykeluarkecil;
                $running += $masuk - $keluar;

                $rows->push((object) [
                    'fwhcode' => $wh->fwhcode,
                    'fprdcode' => $prdKey,
                    'fprdname' => trim((string) $row->fprdname),
                    'fstockmt' => $row->fstockmt,
                    'fstockmtcode' => $row->fstockmtcode,
                    'fstockdate' => $row->fstockdate,
                    'frefno' => $row->frefno,
                    'fsuppliername' => $row->fsuppliername,
                    'fsatuan' => $row->fsatuan,
                    'qtyawalkecil' => 0.0,
                    'qtymasukkecil' => $masuk,
                    'qtykeluarkecil' => $keluar,
                    'qtysaldokecil' => $running,
                    'fminstock' => 0.0,
                ]);
            }
        }

        return $rows->filter(fn($row) => $this->matchStatus($row, $request))->values();
    }

    /**
     * Gabungan movement in + out dilakukan via UNION ALL di SQL,
     * diurutkan oleh database (idealnya pakai index fprdcode+fstockmtdate),
     * bukan Laravel sortBy() yang menahan semua data di memori PHP.
     */
    private function detailUnionQuery(string $whcode, Request $request, string $dateFrom, string $dateTo)
    {
        $in = $this->movementDetailQuery($whcode, $request, $dateFrom, $dateTo, 'in')
            ->selectRaw("d.fprdcode, p.fprdname, m.fstockmtno as fstockmt, m.fstockmtcode, m.fstockmtdate as fstockdate, m.frefno, COALESCE(s.fsuppliername, c.fcustomername, m.fsupplier, m.fket, '') as fsuppliername, COALESCE(p.fsatuankecil,p.fsatuanbesar,p.fsatuanbesar2) as fsatuan, COALESCE(d.fqtykecil,d.fqty,0) as qtymasukkecil, 0 as qtykeluarkecil");

        $out = $this->movementDetailQuery($whcode, $request, $dateFrom, $dateTo, 'out')
            ->selectRaw("d.fprdcode, p.fprdname, m.fstockmtno as fstockmt, m.fstockmtcode, m.fstockmtdate as fstockdate, m.frefno, COALESCE(s.fsuppliername, c.fcustomername, m.fsupplier, m.fket, '') as fsuppliername, COALESCE(p.fsatuankecil,p.fsatuanbesar,p.fsatuanbesar2) as fsatuan, 0 as qtymasukkecil, COALESCE(d.fqtykecil,d.fqty,0) as qtykeluarkecil");

        $inSql = $in->toSql();
        $outSql = $out->toSql();

        return DB::table(DB::raw("({$inSql} UNION ALL {$outSql}) as u"))
            ->mergeBindings($in->getQuery())
            ->mergeBindings($out->getQuery())
            ->orderBy('fprdcode')
            ->orderBy('fstockdate');
    }

    private function warehouses(Request $request)
    {
        $query = DB::table('mswh')->where('fnonactive', '0');
        $this->applyBranchVisibilityScope($query, 'fbranchcode');

        $branches = array_values(array_filter((array) $request->input('branch_codes', [])));
        if ($branches !== []) {
            $query->whereIn('fbranchcode', $branches);
        }
        if ($request->filled('warehouse')) {
            $query->where('fwhcode', $request->input('warehouse'));
        }

        return $query->orderBy('fwhcode')->get(['fwhcode', 'fwhname', 'fbranchcode']);
    }

    private function movementDetailQuery(string $whcode, Request $request, string $dateFrom, string $dateTo, string $direction)
    {
        return $this->movementBaseQuery($whcode, $request, $direction)
            ->where('m.fstockmtdate', '>=', $dateFrom)
            ->where('m.fstockmtdate', '<=', $dateTo . ' 23:59:59')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsuppliercode')
            ->leftJoin('mscustomer as c', 'm.fsupplier', '=', 'c.fcustomercode');
    }

    private function movementBaseQuery(string $whcode, Request $request, string $direction)
    {
        $query = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->where('p.ftype', 'Produk');

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = array_values(array_filter((array) $request->input('branch_codes', [])));
        if ($branches !== []) {
            $query->whereIn('m.fbranchcode', $branches);
        }

        if ($direction === 'in') {
            $query->where(function ($q) use ($whcode) {
                $q->where(fn($qq) => $qq->whereIn('m.fstockmtcode', ['BUY', 'TER', 'REJ'])->where('m.ffrom', $whcode))
                    ->orWhere(fn($qq) => $qq->whereIn('m.fstockmtcode', ['MUT', 'PRD'])->where('m.fto', $whcode))
                    ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'CAB')->where('m.ftrancode', 'M')->where('m.fto', $whcode))
                    ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'ADJ')->where('m.ftrancode', 'M')->where('m.ffrom', $whcode));
            });
        } else {
            $query->where(function ($q) use ($whcode) {
                $q->where(fn($qq) => $qq->whereIn('m.fstockmtcode', ['SRJ', 'PBR', 'REB', 'MUT'])->where('m.ffrom', $whcode))
                    ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'CAB')->where('m.ftrancode', 'K')->where('m.ffrom', $whcode))
                    ->orWhere(fn($qq) => $qq->where('m.fstockmtcode', 'ADJ')->where('m.ftrancode', 'K')->where('m.ffrom', $whcode));
            });
        }

        $this->applyProductFilters($query, $request, 'p');

        return $query;
    }

    private function applyProductFilters($query, Request $request, string $alias): void
    {
        if ($request->filled('group_code')) {
            $query->where("{$alias}.fgroupcode", $request->input('group_code'));
        }
        if ($request->filled('merek')) {
            $query->where("{$alias}.fmerek", $request->input('merek'));
        }
        if ($request->filled('product_from')) {
            $query->where("{$alias}.fprdcode", '>=', $request->input('product_from'));
        }
        if ($request->filled('product_to')) {
            $query->where("{$alias}.fprdcode", '<=', $request->input('product_to'));
        }
    }

    private function matchStatus($row, Request $request): bool
    {
        $status = (string) $request->input('stock_status', 'all');
        $saldo = (float) ($row->qtysaldokecil ?? 0);
        $min = (float) ($row->fminstock ?? 0);

        return match ($status) {
            'not_zero' => $saldo != 0.0,
            'positive' => $saldo > 0,
            'negative' => $saldo < 0,
            'zero' => $saldo == 0.0,
            'below_min' => $min > 0 && $saldo < $min,
            default => true,
        };
    }

    public function exportExcel(Request $request)
    {
        $mode = $request->input('report_mode', 'rekap') === 'detail' ? 'detail' : 'rekap';
        $rows = $mode === 'detail' ? $this->detailRows($request) : $this->rekapRows($request);

        $filename = 'Laporan_Kartu_Stok_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleSubgroup = new Style(fontBold: true, backgroundColor: 'FFE6E6');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        $writer->addRow($makeRow(['LAPORAN KARTU STOK'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y') . '  Jam: ' . date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from . ' s/d ' . $request->date_to]));
        $writer->addRow($makeRow(['Mode:', strtoupper($mode)]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        if ($mode === 'rekap') {
            $writer->addRow($makeRow([
                'No.',
                'Kode Prd',
                'Nama Produk',
                'Isi',
                'Satuan',
                'Saldo Awal',
                'Masuk',
                'Keluar',
                'Saldo Akhir',
                'Gudang'
            ], $styleHeader));

            $groupedWh = $rows->groupBy('fwhcode');
            foreach ($groupedWh as $whcode => $whRows) {
                $writer->addRow($makeRow([
                    'Gudang: ' . $whcode,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ], $styleGroup));

                $groupedGroup = $whRows->groupBy(request('grouping', 'group') === 'merek' ? 'fmerekname' : 'fgroupname');
                foreach ($groupedGroup as $groupName => $items) {
                    $writer->addRow($makeRow([
                        '  Grouping: ' . ($groupName ?: '-'),
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    ], $styleSubgroup));

                    foreach ($items as $index => $row) {
                        $writer->addRow($makeRow([
                            $index + 1,
                            $row->fprdcode,
                            $row->fprdname,
                            (float) $row->qtykecil,
                            $row->fsatuan,
                            (float) $row->qtyawalkecil,
                            (float) $row->qtymasukkecil,
                            (float) $row->qtykeluarkecil,
                            (float) $row->qtysaldokecil,
                            $row->fwhcode
                        ]));
                    }
                }
            }
        } else {
            $writer->addRow($makeRow([
                'Transaksi',
                'Kode Trans',
                'Tanggal',
                'No. Ref',
                'Supplier/Customer',
                'Satuan',
                'Saldo Awal',
                'Masuk',
                'Keluar',
                'Saldo Akhir',
                'Gudang'
            ], $styleHeader));

            $groupedWh = $rows->groupBy('fwhcode');
            foreach ($groupedWh as $whcode => $whRows) {
                $writer->addRow($makeRow([
                    'Gudang: ' . $whcode,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ], $styleGroup));

                $groupedPrd = $whRows->groupBy('fprdcode');
                foreach ($groupedPrd as $prdcode => $items) {
                    $writer->addRow($makeRow([
                        '  Produk: ' . $prdcode . ' - ' . ($items->first()->fprdname ?? ''),
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    ], $styleSubgroup));

                    foreach ($items as $row) {
                        $writer->addRow($makeRow([
                            $row->fstockmt,
                            $row->fstockmtcode,
                            $row->fstockdate ? date('d/m/Y', strtotime($row->fstockdate)) : '',
                            $row->frefno,
                            $row->fsuppliername,
                            $row->fsatuan,
                            (float) $row->qtyawalkecil,
                            (float) $row->qtymasukkecil,
                            (float) $row->qtykeluarkecil,
                            (float) $row->qtysaldokecil,
                            $row->fwhcode
                        ]));
                    }
                }
            }
        }

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
