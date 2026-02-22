<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ReportingAccountController extends Controller
{
  private array $arrDist = [];
  private array $arrNormList = [];
  private array $nameMap = []; // Tambahkan ini untuk mapping nama
  private int $ribu = 1;

  public function index()
  {
    return view('reportingaccount.index');
  }

  public function rebuildAndPrint(Request $request)
  {
    $this->rebuildTreeLogic();
    return $this->printAccount($request);
  }

  private function rebuildTreeLogic()
  {
    return DB::transaction(function () {
      // 1. Ambil faccname juga dari database
      $accounts = DB::table('account')->select('faccount', 'faccupline', 'faccname')->get();
      if ($accounts->isEmpty()) return;

      $this->arrDist = [];
      $this->nameMap = []; // Reset mapping nama

      foreach ($accounts as $row) {
        $acc = trim($row->faccount);
        $upline = trim($row->faccupline ?? '');
        $name = trim($row->faccname ?? '');

        $this->arrDist[$upline][$acc] = null;
        // 2. Simpan nama ke dalam mapping array
        $this->nameMap[$acc] = $name;
      }

      // Cari Root
      $rootAccount = $accounts->first(function ($item) {
        $upline = trim($item->faccupline ?? '');
        return $upline === '' || $upline === '0';
      });

      // Ambil objek root yang benar
      $rootObj = $rootAccount ?? $accounts->first();
      $rootId = trim($rootObj->faccount);
      $rootName = trim($rootObj->faccname ?? '');

      $this->arrNormList = [];
      $nIndx = 1;
      $nLevel = 1;

      // Inisialisasi Root
      $this->arrNormList[$nIndx] = [
        'faccount'      => $rootId,
        'faccountname'  => $rootName, // Isi nama di sini
        'faccupline'    => '',
        'flevel'        => $nLevel,
        'forder'        => $nIndx,
        'fsporder'      => ($nIndx - 1) * $this->ribu,
        'fdxorder'      => ($nIndx + 1) * $this->ribu,
        'fleafend'      => isset($this->arrDist[$rootId]) ? '0' : '1',
      ];

      // Jalankan Rekursif
      $this->traceTree($rootId, $nIndx, $nLevel);

      if (!empty($this->arrNormList)) {
        $lastKey = array_key_last($this->arrNormList);
        $this->arrNormList[$lastKey]['fdxorder'] = 0;
      }

      // Simpan ke Database
      DB::table('accounttree')->truncate();

      // Gunakan array_values untuk memastikan array numerik sebelum chunk
      $dataToInsert = array_values($this->arrNormList);

      foreach (array_chunk($dataToInsert, 1000) as $chunk) {
        DB::table('accounttree')->insert($chunk);
      }
    });
  }

  public function printAccount(Request $request)
  {
    $data = DB::table('accounttree')
      ->orderBy('forder')
      ->get();

    return view('reportingaccount.print', compact('data'));
  }

  private function traceTree($parentAcc, &$nIndx, $nLevel)
  {
    $parentAcc = trim($parentAcc);
    if (!isset($this->arrDist[$parentAcc])) return;

    $nLevel++;
    foreach ($this->arrDist[$parentAcc] as $childAcc => $null) {
      $nIndx++;
      $childAcc = trim($childAcc);

      $this->arrNormList[$nIndx] = [
        'faccount'      => $childAcc,
        'faccountname'  => $this->nameMap[$childAcc] ?? '', // Ambil nama dari mapping
        'faccupline'    => $parentAcc,
        'flevel'        => $nLevel,
        'forder'        => $nIndx,
        'fsporder'      => ($nIndx - 1) * $this->ribu,
        'fdxorder'      => ($nIndx + 1) * $this->ribu,
        'fleafend'      => isset($this->arrDist[$childAcc]) ? '0' : '1',
      ];

      $this->traceTree($childAcc, $nIndx, $nLevel);
    }
  }
}
