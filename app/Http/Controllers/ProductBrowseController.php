<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBrowseController extends Controller
{
    public function index(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $exactCode = trim((string) $request->input('fprdcode_exact', ''));
        $ftypeFilter = trim((string) $request->input('ftype_filter', ''));
        $ftypeExclude = trim((string) $request->input('exclude_type', ''));
        $searchParam = $request->input('search');
        $searchValue = trim(is_array($searchParam) ? ($searchParam['value'] ?? '') : (string) $searchParam);
        $orderColumn = $request->input('order_column', 'fprdname');
        $orderDir = $request->input('order_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $allowedColumns = ['fprdcode', 'fprdname', 'fsatuanbesar', 'fminstock'];
        $orderColumn = in_array($orderColumn, $allowedColumns) ? $orderColumn : 'fprdname';

        // Total tanpa search
        $recordsTotal = DB::table('msprd')
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->where('fapproval', 1)
            ->when($exactCode !== '', function ($q) use ($exactCode) {
                $q->whereRaw('TRIM(fprdcode) = ?', [$exactCode]);
            })
            ->when($ftypeFilter !== '', function ($q) use ($ftypeFilter) {
                $q->whereRaw("LOWER(TRIM(COALESCE(ftype, ''))) = ?", [strtolower($ftypeFilter)]);
            })
            ->when($ftypeExclude !== '', function ($q) use ($ftypeExclude) {
                $q->whereRaw("LOWER(TRIM(COALESCE(ftype, ''))) != ?", [strtolower($ftypeExclude)]);
            })
            ->count();

        // Base untuk filtered count & data
        $baseQuery = fn () => DB::table('msprd')
            ->leftJoin('msmerek', 'msprd.fmerek', '=', 'msmerek.fmerekcode')
            ->whereRaw("COALESCE(TRIM(CAST(msprd.fnonactive AS TEXT)), '0') != '1'")
            ->where('msprd.fapproval', 1)
            ->when($exactCode !== '', function ($q) use ($exactCode) {
                $q->whereRaw('TRIM(msprd.fprdcode) = ?', [$exactCode]);
            })
            ->when($ftypeFilter !== '', function ($q) use ($ftypeFilter) {
                $q->whereRaw("LOWER(TRIM(COALESCE(msprd.ftype, ''))) = ?", [strtolower($ftypeFilter)]);
            })
            ->when($ftypeExclude !== '', function ($q) use ($ftypeExclude) {
                $q->whereRaw("LOWER(TRIM(COALESCE(msprd.ftype, ''))) != ?", [strtolower($ftypeExclude)]);
            })
            ->when($searchValue !== '', function ($q) use ($searchValue) {
                $q->where(function ($w) use ($searchValue) {
                    $w->where('msprd.fprdcode', 'ilike', "%{$searchValue}%")
                        ->orWhere('msprd.fprdname', 'ilike', "%{$searchValue}%");
                });
            });

        // Count pakai query bersih (tanpa DB::raw di select)
        $recordsFiltered = $baseQuery()->count();

        // Data query dengan select lengkap
        $data = $baseQuery()
            ->select([
                'msprd.fprdcode',
                'msprd.fprdname',
                'msprd.ftype',
                'msprd.fmerek',
                'msmerek.fmerekname',
                'msprd.fsatuandefault',
                'msprd.fsatuankecil',
                'msprd.fsatuanbesar',
                'msprd.fsatuanbesar2',
                'msprd.fqtykecil',
                'msprd.fqtykecil2',
                DB::raw("
                CASE 
                    WHEN msprd.fminstock ~ '^[0-9]+(\\.[0-9]+)?$' 
                        THEN (msprd.fminstock)::double precision
                    ELSE 0::double precision
                END AS fminstock
            "),
            ])
            ->orderBy($orderColumn === 'fminstock' ? DB::raw("
            CASE 
                WHEN msprd.fminstock ~ '^[0-9]+(\\.[0-9]+)?$' 
                    THEN (msprd.fminstock)::double precision
                ELSE 0::double precision
            END
        ") : 'msprd.'.$orderColumn, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
