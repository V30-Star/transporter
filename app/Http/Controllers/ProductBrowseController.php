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
        $searchParam = $request->input('search');
        $searchValue = trim(is_array($searchParam) ? ($searchParam['value'] ?? '') : (string) $searchParam);
        $orderColumn = $request->input('order_column', 'fprdname');
        $orderDir = $request->input('order_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $allowedColumns = ['fprdcode', 'fprdname', 'fsatuanbesar', 'fminstock'];
        $orderColumn = in_array($orderColumn, $allowedColumns) ? $orderColumn : 'fprdname';

        // Total tanpa search
        $recordsTotal = DB::table('msprd')
            ->where('fdiscontinue', '!=', 1)
            ->when($exactCode !== '', function ($q) use ($exactCode) {
                $q->whereRaw('TRIM(fprdcode) = ?', [$exactCode]);
            })
            ->count();

        // Base untuk filtered count & data
        $baseQuery = fn () => DB::table('msprd')
            ->leftJoin('msmerek', 'msprd.fmerek', '=', 'msmerek.fmerekid')
            ->where(function ($q) {
                $q->where('msprd.fdiscontinue', '!=', 1)
                    ->orWhereNull('msprd.fdiscontinue');
            })
            ->when($exactCode !== '', function ($q) use ($exactCode) {
                $q->whereRaw('TRIM(msprd.fprdcode) = ?', [$exactCode]);
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
                'msprd.fmerek',
                'msmerek.fmerekname',
                'msprd.fsatuankecil',
                'msprd.fsatuanbesar',
                'msprd.fsatuanbesar2',
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
