<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingAccountController extends Controller
{
  private array $arrDist      = [];   // parent => [children]
  private array $arrNormList  = [];   // hasil tree
  private int   $nIndx        = 0;    // counter global (naik terus saat traverse)

  public function index()
  {
    return view('reportingaccount.index');
  }

  public function rebuildAndPrint(Request $request)
  {
    $this->rebuildTreeLogic();
    return $this->printAccount($request);
  }

  // -----------------------------------------------------------------------
  // REBUILD TREE
  // -----------------------------------------------------------------------
  private function rebuildTreeLogic(): void
  {
    DB::transaction(function () {

      $accounts = DB::table('account')
        ->select('faccount', 'faccupline')
        ->get();

      if ($accounts->isEmpty()) return;

      // -- 1. Bangun arrDist: parent => [child1, child2, ...]
      $this->arrDist = [];
      foreach ($accounts as $row) {
        $acc    = trim($row->faccount);
        $upline = trim($row->faccupline ?? '');
        $this->arrDist[$upline][] = $acc;
      }

      // -- 2. Cari root (upline kosong atau '0')
      $rootAcc = null;
      foreach ($accounts as $row) {
        $upline = trim($row->faccupline ?? '');
        if ($upline === '' || $upline === '0') {
          $rootAcc = trim($row->faccount);
          break;
        }
      }
      if ($rootAcc === null) {
        $rootAcc = trim($accounts->first()->faccount);
      }

      // -- 3. Inisialisasi
      $this->arrNormList = [];
      $this->nIndx       = 1;

      // Root node — fdxorder diisi setelah rekursi selesai
      $this->arrNormList[1] = [
        'faccount'   => $rootAcc,
        'faccupline' => '',
        'flevel'     => 1,
        'forder'     => 1,
        'fsporder'   => 0,          // parent dari root = 0
        'fdxorder'   => 0,          // akan di-patch setelah rekursi
        'fleafend'   => '0',        // root pasti punya anak
      ];

      // -- 4. Rekursi DFS
      $this->traceTree($rootAcc, 1 /*parentOrder*/, 1 /*level*/);

      // -- 5. Patch fdxorder setiap node = forder node terakhir di subtree-nya
      //       Caranya: setelah DFS selesai, nIndx sudah final.
      //       fdxorder node = forder dari elemen tepat sebelum sibling berikutnya.
      //       Tapi cara paling mudah: kita sudah simpan posisi akhir subtree
      //       di dalam rekursi (return value).
      //
      //       Karena traceTree sudah men-patch langsung (lihat implementasi bawah),
      //       root tinggal di-patch dengan nilai nIndx terakhir.
      $this->arrNormList[1]['fdxorder'] = $this->nIndx;

      // -- 6. Simpan ke DB
      DB::table('accounttree')->truncate();

      $rows = array_values($this->arrNormList); // reindex 0-based untuk insert
      foreach (array_chunk($rows, 500) as $chunk) {
        DB::table('accounttree')->insert($chunk);
      }
    });
  }

  // -----------------------------------------------------------------------
  // DFS REKURSIF
  // Mengembalikan forder terakhir yang dipakai dalam subtree node ini.
  // -----------------------------------------------------------------------
  private function traceTree(string $parentAcc, int $parentOrder, int $level): int
  {
    $parentAcc = trim($parentAcc);

    if (!isset($this->arrDist[$parentAcc])) {
      // Node daun — tidak punya anak
      return $this->nIndx;
    }

    $children  = $this->arrDist[$parentAcc];
    $lastIndex = count($children) - 1;
    $level++;

    foreach ($children as $i => $childAcc) {
      $childAcc  = trim($childAcc);
      $this->nIndx++;
      $myOrder   = $this->nIndx;
      $isLastChild = ($i === $lastIndex);

      // Tulis dulu dengan fdxorder sementara (akan di-patch setelah rekursi)
      $this->arrNormList[$myOrder] = [
        'faccount'   => $childAcc,
        'faccupline' => $parentAcc,
        'flevel'     => $level,
        'forder'     => $myOrder,
        'fsporder'   => $parentOrder,   // forder dari parent
        'fdxorder'   => $myOrder,       // sementara; di-patch setelah rekursi
        'fleafend'   => $isLastChild ? '1' : '0',
      ];

      // Rekursi → dapat forder terakhir subtree anak ini
      $lastInSubtree = $this->traceTree($childAcc, $myOrder, $level);

      // Patch fdxorder node ini = forder node terakhir di subtree-nya
      $this->arrNormList[$myOrder]['fdxorder'] = $lastInSubtree;
    }

    // Kembalikan forder terakhir yang sudah dipakai (= nIndx sekarang)
    return $this->nIndx;
  }

  // -----------------------------------------------------------------------
  // PRINT / PREVIEW
  // -----------------------------------------------------------------------
  public function printAccount(Request $request)
  {
    $data = DB::table('accounttree')
      ->join('account', 'accounttree.faccount', '=', 'account.faccount')
      ->select('accounttree.*', 'account.faccname')
      ->orderBy('forder')
      ->get();

    return view('reportingaccount.print', compact('data'));
  }
}
