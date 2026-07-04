<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'user_session' => auth()->user(),
        ]);
    }

    private function rekapRows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfYear()->toDateString());
        $warehouses = $this->warehouses($request);
        $card = [];

        foreach ($warehouses as $wh) {
            $this->seedProducts($card, $wh->fwhcode, $request);
            $this->applyMovementTotals($card, $wh->fwhcode, $request, null, $dateFrom, 'opening');
            $this->applyMovementTotals($card, $wh->fwhcode, $request, $dateFrom, $dateTo, 'period');
        }

        return collect($card)
            ->map(function ($row) {
                $row['qtysaldokecil'] = $row['qtyawalkecil'] + $row['qtymasukkecil'] - $row['qtykeluarkecil'];
                return (object) $row;
            })
            ->filter(fn($row) => $this->matchStatus($row, $request))
            ->sortBy([
                ['fwhcode', 'asc'],
                ['fgroupname', 'asc'],
                ['fprdcode', 'asc'],
            ])
            ->values();
    }

    private function detailRows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfYear()->toDateString());
        $warehouses = $this->warehouses($request);
        $rows = collect();

        foreach ($warehouses as $wh) {
            $opening = [];
            $this->seedProducts($opening, $wh->fwhcode, $request);
            $this->applyMovementTotals($opening, $wh->fwhcode, $request, null, $dateFrom, 'opening');

            foreach ($opening as $row) {
                if ((float) $row['qtyawalkecil'] !== 0.0) {
                    $rows->push((object) array_merge($row, [
                        'fstockmt' => 'Saldo Awal',
                        'fstockmtcode' => '',
                        'fstockdate' => null,
                        'frefno' => '',
                        'fsuppliername' => '',
                        'qtymasukkecil' => 0.0,
                        'qtykeluarkecil' => 0.0,
                        'priority' => '0',
                    ]));
                }
            }

            $this->movementDetailQuery($wh->fwhcode, $request, $dateFrom, $dateTo, 'in')->get()->each(function ($row) use ($rows, $wh) {
                $rows->push($this->detailObject($row, $wh->fwhcode, (float) $row->fqtykecil, 0.0, '1'));
            });
            $this->movementDetailQuery($wh->fwhcode, $request, $dateFrom, $dateTo, 'out')->get()->each(function ($row) use ($rows, $wh) {
                $rows->push($this->detailObject($row, $wh->fwhcode, 0.0, (float) $row->fqtykecil, '2'));
            });
        }

        $running = [];

        return $rows->sortBy([
                ['fwhcode', 'asc'],
                ['fprdcode', 'asc'],
                ['fstockdate', 'asc'],
                ['priority', 'asc'],
                ['fstockmt', 'asc'],
            ])
            ->map(function ($row) use (&$running) {
                $key = trim((string) $row->fwhcode) . '|' . trim((string) $row->fprdcode);
                $running[$key] = ($running[$key] ?? 0.0) + (float) $row->qtyawalkecil + (float) $row->qtymasukkecil - (float) $row->qtykeluarkecil;
                $row->qtysaldokecil = $running[$key];
                return $row;
            })
            ->filter(fn($row) => $this->matchStatus($row, $request))
            ->values();
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

    private function seedProducts(array &$card, string $whcode, Request $request): void
    {
        $query = DB::table('msprd as p')
            ->leftJoin('prdwh as w', function ($join) use ($whcode) {
                $join->on('p.fprdcode', '=', 'w.fprdcode')->where('w.fwhcode', $whcode);
            })
            ->leftJoin('ms_groupprd as g', 'p.fgroupcode', '=', 'g.fgroupcode')
            ->leftJoin('msmerek as mr', 'p.fmerek', '=', 'mr.fmerekcode')
            ->where('p.ftype', 'Produk')
            ->selectRaw("p.fprdcode, p.fprdname, p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2, p.fqtykecil, p.fgroupcode, COALESCE(g.fgroupname, p.fgroupcode) AS fgroupname, p.fmerek, COALESCE(mr.fmerekname, p.fmerek) AS fmerekname, COALESCE(CAST(NULLIF(p.fminstock::text, '') AS NUMERIC), 0) * COALESCE(CAST(NULLIF(p.fqtykecil::text, '') AS NUMERIC), 1) AS fminstock, COALESCE(CAST(NULLIF(w.fawal::text, '') AS NUMERIC), 0) AS fawal");

        $this->applyProductFilters($query, $request, 'p');

        foreach ($query->get() as $row) {
            $key = $whcode . '|' . trim($row->fprdcode);
            $card[$key] = [
                'fwhcode' => $whcode,
                'fprdcode' => trim($row->fprdcode),
                'fprdname' => trim((string) $row->fprdname),
                'qtykecil' => (float) ($row->fqtykecil ?: 1),
                'fsatuankecil' => trim((string) $row->fsatuankecil),
                'fsatuanbesar' => trim((string) $row->fsatuanbesar),
                'fsatuanbesar2' => trim((string) $row->fsatuanbesar2),
                'fsatuan' => trim((string) ($row->fsatuankecil ?: $row->fsatuanbesar ?: $row->fsatuanbesar2)),
                'fgroupcode' => trim((string) $row->fgroupcode),
                'fgroupname' => trim((string) $row->fgroupname),
                'fmerek' => trim((string) $row->fmerek),
                'fmerekname' => trim((string) $row->fmerekname),
                'fminstock' => (float) $row->fminstock,
                'qtyawalkecil' => (float) $row->fawal,
                'qtymasukkecil' => 0.0,
                'qtykeluarkecil' => 0.0,
                'qtysaldokecil' => 0.0,
            ];
        }
    }

    private function applyMovementTotals(array &$card, string $whcode, Request $request, ?string $dateFrom, string $dateTo, string $phase): void
    {
        foreach (['in', 'out'] as $direction) {
            $this->movementTotalQuery($whcode, $request, $dateFrom, $dateTo, $direction)->get()->each(function ($row) use (&$card, $whcode, $direction, $phase) {
                $key = $whcode . '|' . trim($row->fprdcode);
                if (!isset($card[$key])) {
                    return;
                }
                $qty = (float) $row->qty;
                if ($phase === 'opening') {
                    $card[$key]['qtyawalkecil'] += $direction === 'in' ? $qty : -$qty;
                } elseif ($direction === 'in') {
                    $card[$key]['qtymasukkecil'] += $qty;
                } else {
                    $card[$key]['qtykeluarkecil'] += $qty;
                }
            });
        }
    }

    private function movementTotalQuery(string $whcode, Request $request, ?string $dateFrom, string $dateTo, string $direction)
    {
        $query = $this->movementBaseQuery($whcode, $request, $direction)
            ->selectRaw('d.fprdcode, SUM(COALESCE(d.fqtykecil, d.fqty, 0)) AS qty')
            ->groupBy('d.fprdcode');

        if ($dateFrom) {
            $query->where('m.fstockmtdate', '>=', $dateFrom);
        } else {
            $query->where('m.fstockmtdate', '<', $dateTo);
        }
        if ($dateFrom) {
            $query->where('m.fstockmtdate', '<=', $dateTo . ' 23:59:59');
        }

        return $query;
    }

    private function movementDetailQuery(string $whcode, Request $request, string $dateFrom, string $dateTo, string $direction)
    {
        return $this->movementBaseQuery($whcode, $request, $direction)
            ->where('m.fstockmtdate', '>=', $dateFrom)
            ->where('m.fstockmtdate', '<=', $dateTo . ' 23:59:59')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsuppliercode')
            ->leftJoin('mscustomer as c', 'm.fsupplier', '=', 'c.fcustomercode')
            ->selectRaw("m.fstockmtno AS fstockmt, m.fstockmtcode, m.fstockmtdate AS fstockdate, m.frefno, COALESCE(s.fsuppliername, c.fcustomername, m.fsupplier, m.fket, '') AS fsuppliername, d.fprdcode, p.fprdname, p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2, COALESCE(d.fqtykecil, d.fqty, 0) AS fqtykecil");
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

    private function detailObject($row, string $whcode, float $masuk, float $keluar, string $priority): object
    {
        $satuan = trim((string) ($row->fsatuankecil ?: $row->fsatuanbesar ?: $row->fsatuanbesar2));
        return (object) [
            'fwhcode' => $whcode,
            'fprdcode' => trim($row->fprdcode),
            'fprdname' => trim((string) $row->fprdname),
            'fstockmt' => $row->fstockmt,
            'fstockmtcode' => $row->fstockmtcode,
            'fstockdate' => $row->fstockdate,
            'frefno' => $row->frefno,
            'fsuppliername' => $row->fsuppliername,
            'fsatuan' => $satuan,
            'qtyawalkecil' => 0.0,
            'qtymasukkecil' => $masuk,
            'qtykeluarkecil' => $keluar,
            'qtysaldokecil' => 0.0,
            'fminstock' => 0.0,
            'priority' => $priority,
        ];
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
}
