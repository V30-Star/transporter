<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBrowseController extends Controller
{
  public function index(Request $request)
  {
    // DataTables parameters
    $draw = (int) $request->input('draw', 1);
    $start = (int) $request->input('start', 0);
    $length = (int) $request->input('length', 10);
    $searchValue = $request->input('search', '');
    $orderColumn = $request->input('order_column', 'fprdname');
    $orderDir = $request->input('order_dir', 'asc');

    // Base query dengan filter fdiscontinue
    $builder = DB::table('msprd')
      ->leftJoin('msmerek', 'msprd.fmerek', '=', 'msmerek.fmerekid')
      ->where('msprd.fdiscontinue', '!=', 1)
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
      ]);

    // Total records tanpa filter (dengan filter discontinued)
    $recordsTotal = DB::table('msprd')
      ->where('fdiscontinue', '!=', 1)
      ->count();

    // Search/Filter
    if ($searchValue !== '') {
      $builder->where(function ($w) use ($searchValue) {
        $w->where('msprd.fprdcode', 'ilike', "%{$searchValue}%")
          ->orWhere('msprd.fprdname', 'ilike', "%{$searchValue}%");
      });
    }

    // Total records setelah filter
    $recordsFiltered = $builder->count();

    // Sorting
    $allowedColumns = ['fprdcode', 'fprdname', 'fsatuanbesar', 'fminstock'];
    if (in_array($orderColumn, $allowedColumns)) {
      $builder->orderBy('msprd.' . $orderColumn, $orderDir);
    } else {
      $builder->orderBy('msprd.fprdname', 'asc');
    }

    // Pagination
    $data = $builder->skip($start)
      ->take($length)
      ->get();

    // Response format untuk DataTables
    return response()->json([
      'draw' => $draw,
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => $recordsFiltered,
      'data' => $data
    ]);
  }
}
