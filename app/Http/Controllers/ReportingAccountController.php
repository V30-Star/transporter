<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class ReportingAccountController extends Controller
{
  private array $arrDist      = [];   // parent => [children]
  private array $arrNormList  = [];   // hasil tree
  private int   $nIndx        = 0;    // counter global (naik terus saat traverse)

  public function index()
  {
    $accounts = DB::table('account')
      ->select('faccount', 'faccname')
      ->orderBy('faccount', 'asc')
      ->get();

    return view('reportingaccount.index', compact('accounts'));
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

      $this->arrDist = [];
      foreach ($accounts as $row) {
        $acc    = trim($row->faccount);
        $upline = trim($row->faccupline ?? '');
        $this->arrDist[$upline][] = $acc;
      }

      foreach ($this->arrDist as &$children) {
        sort($children);
      }
      unset($children);

      $this->arrNormList = [];
      $this->nIndx = 0;

      // Ambil semua top-level accounts (upline = '' atau '0')
      // SKIP node "0" sendiri, langsung ambil yang upline-nya '0' atau ''
      $topLevelAccounts = [];
      foreach ($accounts as $row) {
        $acc    = trim($row->faccount);
        $upline = trim($row->faccupline ?? '');
        if (($upline === '' || $upline === '0') && $acc !== '0') {
          $topLevelAccounts[] = $acc;
        }
      }
      sort($topLevelAccounts);

      // Buat virtual root "0" sebagai container
      $this->nIndx = 1;
      $this->arrNormList[1] = [
        'faccount'   => '0',
        'faccupline' => '',
        'flevel'     => 1,
        'forder'     => 1,
        'fsporder'   => 0,
        'fdxorder'   => 0,
        'fleafend'   => '0',
      ];

      // Override arrDist["0"] dengan topLevelAccounts yang sudah disorted
      $this->arrDist['0'] = $topLevelAccounts;

      // Traverse dari virtual root
      $this->traceTree('0', 1, 1);
      $this->arrNormList[1]['fdxorder'] = $this->nIndx;

      DB::table('accounttree')->truncate();
      foreach (array_chunk(array_values($this->arrNormList), 500) as $chunk) {
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
    // 1. Ambil fdxorder dari tabel accounttree (bukan account)
    // Gunakan nama kolom yang benar: fdxorder
    $startOrder = DB::table('accounttree')
      ->where('faccount', $request->account_from)
      ->value('fdxorder');

    $endOrder = DB::table('accounttree')
      ->where('faccount', $request->account_to)
      ->value('fdxorder');

    // 2. Query data utama dengan join
    $query = DB::table('accounttree')
      ->join('account', 'accounttree.faccount', '=', 'account.faccount')
      ->select(
        'accounttree.*', // Ini akan mengambil faccount, fdxorder, forder, flevel, dll
        'account.faccname',
        'account.fhavesubaccount',
        'account.fnormal',
        'account.fend'
      );

    // 3. Filter berdasarkan fdxorder jika parameter ada
    if ($startOrder !== null && $endOrder !== null) {
      $query->whereBetween('accounttree.fdxorder', [$startOrder, $endOrder]);
    }

    // 4. Urutkan berdasarkan fdxorder agar hirarki tetap rapi
    $data = $query->orderBy('accounttree.fdxorder', 'asc')->get();

    return view('reportingaccount.print', compact('data'));
  }
}
