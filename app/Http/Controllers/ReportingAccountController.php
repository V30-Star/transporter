<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

class ReportingAccountController extends Controller
{
  private array $arrDist     = [];
  private array $arrNormList = [];
  private int   $nIndx       = 0;

  public function index()
  {
    $accounts = DB::table('accounttree')
      ->join('account', 'accounttree.faccount', '=', 'account.faccount')
      ->select('account.faccount', 'account.faccname', 'accounttree.fdxorder', 'accounttree.forder')
      ->orderBy('account.faccount', 'asc')
      ->get();

    return view('reportingaccount.index', compact('accounts'));
  }

  public function rebuildAndPrint(Request $request)
  {
    $this->rebuildTreeLogic();
    return $this->printAccount($request);
  }

  private function rebuildTreeLogic(): void
  {
    DB::transaction(function () {
      $accounts = DB::table('account')->select('faccount', 'faccupline')->get();
      if ($accounts->isEmpty()) return;

      $this->arrDist = [];
      foreach ($accounts as $row) {
        $acc    = trim($row->faccount);
        $upline = trim($row->faccupline ?? '');
        $this->arrDist[$upline][] = $acc;
      }
      foreach ($this->arrDist as &$children) sort($children);
      unset($children);

      $this->arrNormList = [];
      $this->nIndx = 0;

      $topLevelAccounts = [];
      foreach ($accounts as $row) {
        $acc    = trim($row->faccount);
        $upline = trim($row->faccupline ?? '');
        if (($upline === '' || $upline === '0') && $acc !== '0') {
          $topLevelAccounts[] = $acc;
        }
      }
      sort($topLevelAccounts);

      $this->nIndx = 1;
      $this->arrNormList[1] = [
        'faccount' => '0',
        'faccupline' => '',
        'flevel' => 1,
        'forder' => 1,
        'fsporder' => 0,
        'fdxorder' => 0,
        'fleafend' => '0',
      ];

      $this->arrDist['0'] = $topLevelAccounts;
      $this->traceTree('0', 1, 1);
      $this->arrNormList[1]['fdxorder'] = $this->nIndx;

      DB::table('accounttree')->truncate();
      foreach (array_chunk(array_values($this->arrNormList), 500) as $chunk) {
        DB::table('accounttree')->insert($chunk);
      }
    });
  }

  private function traceTree(string $parentAcc, int $parentOrder, int $level): int
  {
    $parentAcc = trim($parentAcc);
    if (!isset($this->arrDist[$parentAcc])) return $this->nIndx;

    $children  = $this->arrDist[$parentAcc];
    $lastIndex = count($children) - 1;
    $level++;

    foreach ($children as $i => $childAcc) {
      $childAcc    = trim($childAcc);
      $this->nIndx++;
      $myOrder     = $this->nIndx;
      $isLastChild = ($i === $lastIndex);

      $this->arrNormList[$myOrder] = [
        'faccount'   => $childAcc,
        'faccupline' => $parentAcc,
        'flevel'     => $level,
        'forder'     => $myOrder,
        'fsporder'   => $parentOrder,
        'fdxorder'   => $myOrder,
        'fleafend'   => $isLastChild ? '1' : '0',
      ];

      $lastInSubtree = $this->traceTree($childAcc, $myOrder, $level);
      $this->arrNormList[$myOrder]['fdxorder'] = $lastInSubtree;
    }

    return $this->nIndx;
  }

  public function printAccount(Request $request)
  {
    $data = $this->getReportData($request);
    return view('reportingaccount.print', ['data' => $data, 'user_session' => auth()->user()]);
  }

  public function exportExcel(Request $request)
  {
    $data = $this->getReportData($request);

    $filename = "Chart_of_Account_" . date('YmdHis') . ".xlsx";
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

    $writer = new Writer();
    $writer->openToFile($tempFile);

    // --- Styles ---
    $styleTitle      = new Style(fontBold: true, fontSize: 14);
    $styleHeader     = new Style(fontBold: true, backgroundColor: 'D3D3D3');
    $styleRowHeader  = new Style(fontBold: true, backgroundColor: 'EEF2FF'); // akun header (non-detail)
    $styleRowDetail  = new Style(fontBold: false);
    $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

    $makeRow = function (array $values, ?Style $style = null): Row {
      $cells = array_map(
        fn($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
        $values
      );
      return new Row($cells);
    };

    // --- Header Informasi ---
    $writer->addRow($makeRow(['ACCOUNT TREE REPORT'], $styleTitle));
    $writer->addRow($makeRow(['Tanggal:', date('d/m/Y') . '  Jam: ' . date('H:i')]));
    $writer->addRow($makeRow([]));

    // --- Header Kolom ---
    $writer->addRow($makeRow([
      'Level',
      'Kode Akun',
      'Nama Akun',
      'Tipe',
      'D/K',
      'Sub Account',
    ], $styleHeader));

    $totalRows = 0;

    foreach ($data as $row) {
      $indent  = str_repeat('    ', max(0, $row->flevel - 2)); // indentasi per level
      $isLeaf  = $row->fend == '1';
      $style   = $isLeaf ? $styleRowDetail : $styleRowHeader;

      $writer->addRow($makeRow([
        (int) $row->flevel,
        trim($row->faccount),
        $indent . trim($row->faccname),
        $isLeaf ? 'Detil' : 'Header',
        $row->fnormal == 'D' ? 'Debit' : 'Kredit',
        $row->fhavesubaccount == '1' ? 'Yes' : 'No',
      ], $style));

      $totalRows++;
    }

    // --- Footer ---
    $writer->addRow($makeRow([]));
    $writer->addRow($makeRow([
      'Total Akun: ' . $totalRows,
      '',
      '',
      '',
      '',
      ''
    ], $styleGrandTotal));
    $writer->addRow($makeRow(['*** End of Report ***'], $styleGrandTotal));

    $writer->close();

    return response()->download($tempFile, $filename, [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ])->deleteFileAfterSend(true);
  }

  private function getReportData(Request $request)
  {
    $cforderMin = DB::table('accounttree')
      ->where('faccount', $request->account_from)
      ->value('forder');

    $cforderMax = DB::table('accounttree')
      ->where('faccount', $request->account_to)
      ->value('forder');

    $query = DB::table('account as d')
      ->join('accounttree as t', 'd.faccount', '=', 't.faccount')
      ->select(
        'd.*',
        't.*',
        DB::raw("CASE WHEN d.fend = '1' THEN CAST('Detil' AS char(8)) ELSE CAST('Header' AS char(8)) END as fjudul"),
        DB::raw("CASE WHEN d.fhavesubaccount = '1' THEN CAST('Ya' AS char(5)) ELSE CAST('Tidak' AS char(5)) END as fsubacc")
      );

    if ($cforderMin !== null && $cforderMax !== null) {
      $query->whereRaw('CAST(t.forder AS int) >= ?', [(int) $cforderMin])
        ->whereRaw('CAST(t.forder AS int) <= ?', [(int) $cforderMax]);
    }

    return $query->orderBy('t.forder', 'asc')->get();
  }
}
