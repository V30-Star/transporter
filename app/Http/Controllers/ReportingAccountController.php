<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ReportingAccountController extends Controller
{
  private array $arrDist = [];
  private array $arrNormList = [];
  private int $ribu = 1;

  public function index()
  {
    return view('reportingaccount.index');
  }

  public function rebuildAndPrint(Request $request)
  {
    // 1. Jalankan proses rebuild logic
    // Kita panggil method rebuildTree secara internal
    $this->rebuildTreeLogic();

    // 2. Setelah selesai rebuild, arahkan ke fungsi print atau tampilkan view
    // Di sini saya asumsikan kamu mau lanjat ke halaman preview/print
    return $this->printAccount($request);
  }

  private function rebuildTreeLogic()
  {
    return DB::transaction(function () {
      $accounts = DB::table('account')->select('faccount', 'faccupline')->get();
      if ($accounts->isEmpty()) return;

      $this->arrDist = [];
      foreach ($accounts as $row) {
        $acc = trim($row->faccount);
        $upline = trim($row->faccupline ?? '');
        $this->arrDist[$upline][$acc] = null;
      }

      // Cari Root
      $rootAccount = $accounts->first(function ($item) {
        $upline = trim($item->faccupline ?? '');
        return $upline === '' || $upline === '0';
      });

      $rootId = $rootAccount ? trim($rootAccount->faccount) : trim($accounts->first()->faccount);

      $this->arrNormList = [];
      $nIndx = 1; // Mulai dari 1
      $nLevel = 1;

      // Inisialisasi Root
      $this->arrNormList[$nIndx] = [
        'faccount'   => $rootId,
        'faccupline' => '',
        'flevel'     => $nLevel,
        'forder'     => $nIndx, // Kolom forder jadi 1, 2, 3...
        'fsporder'   => ($nIndx - 1) * $this->ribu, // (1-1) * 1000 = 0
        'fdxorder'   => ($nIndx + 1) * $this->ribu, // (1+1) * 1000 = 2000
        'fleafend'   => isset($this->arrDist[$rootId]) ? '0' : '1',
      ];

      // Jalankan Rekursif
      $this->traceTree($rootId, $nIndx, $nLevel);

      // Koreksi fdxorder untuk item terakhir agar jadi 0
      if (!empty($this->arrNormList)) {
        $lastKey = array_key_last($this->arrNormList);
        $this->arrNormList[$lastKey]['fdxorder'] = 0;
      }

      // Simpan ke Database
      DB::table('accounttree')->truncate();
      $dataToInsert = [];
      foreach ($this->arrNormList as $val) {
        $dataToInsert[] = $val;
      }

      foreach (array_chunk($dataToInsert, 1000) as $chunk) {
        DB::table('accounttree')->insert($chunk);
      }
    });
  }

  /**
   * Fungsi untuk handle tampilan Print/Preview
   */
  public function printAccount(Request $request)
  {
    $data = DB::table('accounttree')
      ->orderBy('forder') // Sangat penting urut berdasarkan forder!
      ->get();

    return view('reportingaccount.print', compact('data'));
  }

  /**
   * Pengganti TraceTree legacy
   */
  private function traceTree($parentAcc, &$nIndx, $nLevel)
  {
    $parentAcc = trim($parentAcc);
    if (!isset($this->arrDist[$parentAcc])) return;

    $nLevel++;
    foreach ($this->arrDist[$parentAcc] as $childAcc => $null) {
      $nIndx++; // Indeks naik terus (2, 3, 4...)
      $childAcc = trim($childAcc);

      $this->arrNormList[$nIndx] = [
        'faccount'   => $childAcc,
        'faccupline' => $parentAcc,
        'flevel'     => $nLevel,
        'forder'     => $nIndx,
        'fsporder'   => ($nIndx - 1) * $this->ribu,
        'fdxorder'   => ($nIndx + 1) * $this->ribu,
        'fleafend'   => isset($this->arrDist[$childAcc]) ? '0' : '1',
      ];

      $this->traceTree($childAcc, $nIndx, $nLevel);
    }
  }
}
