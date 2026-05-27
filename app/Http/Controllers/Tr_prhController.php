<?php

namespace App\Http\Controllers;

use App\Mail\ApprovalEmail;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class Tr_prhController extends Controller
{
    private function resolveProductDefaultUnit(object $product): string
    {
        $defaultKey = trim((string) ($product->fsatuandefault ?? ''));

        return match ($defaultKey) {
            '1' => trim((string) ($product->fsatuankecil ?? '')),
            '2' => trim((string) ($product->fsatuanbesar ?? '')),
            '3' => trim((string) ($product->fsatuanbesar2 ?? '')),
            default => trim((string) ($product->fsatuankecil ?? ''))
                ?: trim((string) ($product->fsatuanbesar ?? ''))
                ?: trim((string) ($product->fsatuanbesar2 ?? '')),
        };
    }

    private function canApprovePurchaseRequest(): bool
    {
        return in_array('approvePR', explode(',', session('user_restricted_permissions', '')));
    }

    private function getApprovalRecipients(): array
    {
        return array_values(array_filter([
            trim((string) config('approval.purchase_request.stage1', '')),
            trim((string) config('approval.purchase_request.stage2', '')),
        ]));
    }

    public function index(Request $request)
    {
        $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $status = trim((string) $request->query('status', 'all'));
        $year = $request->query('year');
        $month = $request->query('month');

        $availableYearsQuery = Tr_prh::selectRaw('DISTINCT EXTRACT(YEAR FROM tr_prh.fcreatedat) as year')
            ->whereNotNull('tr_prh.fcreatedat');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'tr_prh.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM tr_prh.fcreatedat) DESC')
            ->pluck('year');

        if ($request->ajax()) {
            $query = Tr_prh::query()
                ->leftJoin('mssupplier', 'tr_prh.fsupplier', '=', 'mssupplier.fsuppliercode');
            $this->applyBranchVisibilityScope($query, 'tr_prh.fbranchcode');
            $totalRecords = (clone $query)->count();

            $searchableColumns = ['tr_prh.fprno', 'tr_prh.fprdin', 'mssupplier.fsuppliername'];

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }

            $statusFilter = trim((string) $request->query('status', 'all'));
            if ($statusFilter === 'open') {
                $query->where('tr_prh.fclose', '0')
                    ->where(function ($statusQuery) {
                        $statusQuery->whereNull('tr_prh.fprdin')
                            ->orWhere('tr_prh.fprdin', '')
                            ->orWhere('tr_prh.fprdin', '0');
                    });
            } elseif ($statusFilter === 'done') {
                $query->where('tr_prh.fclose', '0')
                    ->where('tr_prh.fprdin', '1');
            } elseif ($statusFilter === 'partial') {
                $query->where('tr_prh.fclose', '0')
                    ->where('tr_prh.fprdin', '2');
            } elseif ($statusFilter === 'close') {
                $query->where('tr_prh.fclose', '1');
            } elseif ($statusFilter === 'active') {
                $query->where('tr_prh.fclose', '0');
            } elseif ($statusFilter === 'nonactive') {
                $query->where('tr_prh.fclose', '1');
            } elseif ($statusFilter === 'belum_approve') {
                $query->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('tr_prh.fapproval', '1')
                              ->where(function ($sub) {
                                  $sub->whereNull('tr_prh.fapproval2')
                                      ->orWhere('tr_prh.fapproval2', '!=', '2');
                              });
                    })->orWhere(function ($inner) {
                        $inner->where('tr_prh.fapproval2', '1')
                              ->where(function ($sub) {
                                  $sub->whereNull('tr_prh.fapproval')
                                      ->orWhere('tr_prh.fapproval', '!=', '2');
                              });
                    });
                });
            } elseif ($statusFilter === 'approve') {
                $query->where(function ($q) {
                    $q->where('tr_prh.fapproval', '2')
                      ->orWhere('tr_prh.fapproval2', '2');
                });
            }
            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM tr_prh.fcreatedat) = ?', [$year]);
            }

            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM tr_prh.fcreatedat) = ?', [$month]);
            }

            $filteredRecords = (clone $query)->count();

            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');

            $columns = [
                0 => 'tr_prh.fprno',
                1 => 'tr_prh.fprdate',
                2 => 'mssupplier.fsuppliername',
                3 => 'tr_prh.fusercreate',
                4 => 'tr_prh.fclose',
                5 => 'tr_prh.fapproval',
                6 => '',
            ];

            if (isset($columns[$orderColumnIndex]) && $columns[$orderColumnIndex] !== null && $columns[$orderColumnIndex] !== '') {
                $query->orderBy($columns[$orderColumnIndex], $orderDir);
            } else {
                $query->orderBy('tr_prh.fprhid', 'desc');
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            if ($length != -1) {
                $query->skip($start)->take($length);
            }

            $records = $query->get([
                'tr_prh.fprhid',
                'tr_prh.fprno',
                'tr_prh.fprdate',
                'tr_prh.fsupplier',
                'tr_prh.fusercreate',
                'tr_prh.fuserupdate',
                'tr_prh.fclose',
                'tr_prh.fprdin',
                'tr_prh.fapproval',
                'tr_prh.fapproval2',
                'mssupplier.fsuppliername'
            ]);

            $data = $records->map(function ($record) {
                return [
                    'fprno' => $record->fprno,
                    'fprdate' => $record->fprdate ? Carbon::parse($record->fprdate)->format('Y-m-d') : null,
                    'fsuppliername' => $record->fsuppliername,
                    'display_user' => $record->fuserupdate ?: $record->fusercreate,
                    'fuserupdate' => $record->fuserupdate,
                    'fclose' => $record->fclose,
                    'fprdin' => $record->fprdin,
                    'fapproval' => $record->fapproval,
                    'fapproval2' => $record->fapproval2,
                    'fprhid' => $record->fprhid,
                    'DT_RowId' => 'row_'.$record->fprhid,
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        return view('tr_prh.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            'status',
            'availableYears',
            'year',
            'month'
        ));
    }

    private function generatetr_prh_Code(?Carbon $onDate = null, $branch = null): string
    {
        $date = $onDate ?: now();

        $branch = $branch
            ?? Auth::guard('sysuser')->user()?->fcabang
            ?? Auth::user()?->fcabang
            ?? null;

        $kodeCabang = null;

        if ($branch !== null) {
            $needle = trim((string) $branch);

            if (is_numeric($needle)) {
                $kodeCabang = DB::table('mscabang')
                    ->where('fcabangid', (int) $needle)
                    ->value('fcabangkode');
            } else {
                $kodeCabang = DB::table('mscabang')
                    ->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$needle])
                    ->value('fcabangkode');

                if (! $kodeCabang) {
                    $kodeCabang = DB::table('mscabang')
                        ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
                        ->value('fcabangkode');
                }
            }
        }

        if (! $kodeCabang) {
            $kodeCabang = 'NA';
        }

        $prefix = sprintf('PR.%s.%s.%s.', trim($kodeCabang), $date->format('y'), $date->format('m'));

        return DB::transaction(function () use ($prefix) {
            $last = \App\Models\Tr_prh::where('fprno', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('fprno')
                ->first();

            $lastNum = 0;
            if ($last && ($pos = strrpos($last->fprno, '.')) !== false) {
                $lastNum = (int) substr($last->fprno, $pos + 1);
            }

            $next = str_pad((string) ($lastNum + 1), 4, '0', STR_PAD_LEFT);

            return $prefix.$next;
        });
    }

    public function print(string $fprno)
    {
        $supplierSub = (new Supplier)->getTable();

        $hdr = Tr_prh::query()
            ->leftJoin("{$supplierSub} as s", 's.fsuppliercode', '=', 'tr_prh.fsupplier')
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_prh.fbranchcode')
            ->where('tr_prh.fprno', $fprno)
            ->first([
                'tr_prh.*',
                's.fsuppliername as supplier_name',
                'c.fcabangname as cabang_name',
            ]);

        abort_if(! $hdr, 404);

        $dt = Tr_prd::query()
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'tr_prd.fprdcode')
            ->where('tr_prd.fprno', $hdr->fprno)
            ->orderBy('p.fprdname')
            ->get([
                'tr_prd.*',
                'p.fprdname as product_name',
                'p.fprdcode as product_code',
                'p.fminstock as stock',
            ]);

        $fmt = fn ($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('tr_prh.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        $branchInfo = $this->getCurrentBranchInfo();
        $canApproval = $this->canApprovePurchaseRequest();
        $suppliers = $this->getSuppliers();
        $fbranchcode = $branchInfo['fbranchcode'];

        $newtr_prh_code = $this->generatetr_prh_Code(now(), $fbranchcode);
        $products = $this->getProducts();
        $productMap = $this->buildProductMap($products);

        return view('tr_prh.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'perms' => ['can_approval' => $canApproval],
            'suppliers' => $suppliers,
            'fcabang' => $branchInfo['fcabang'],
            'fbranchlabel' => $branchInfo['fbranchlabel'],
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function store(Request $request)
    {
        $this->validateStoreRequest($request);
        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

        $fprdate = $request->filled('fprdate')
            ? Carbon::parse($request->fprdate)->startOfDay()
            : now()->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fprdate);

        $branchFromForm = $request->input('fbranchcode');
        $fprno = $request->filled('fprno')
            ? $request->fprno
            : $this->generatetr_prh_Code($fprdate, $branchFromForm);

        $fneeddate = $request->filled('fneeddate') ? Carbon::parse($request->fneeddate)->startOfDay() : null;
        $fduedate = $request->filled('fduedate') ? Carbon::parse($request->fduedate)->startOfDay() : null;

        $userName = $this->getAuthenticatedUserName();

        ['codes' => $codes, 'sats' => $sats, 'qtys' => $qtys, 'noacaks' => $noacaks, 'descs' => $descs, 'ketdts' => $ketdts] = $this->getDetailInputArrays($request);
        $productMap = $this->getRequestedProductsByCode($codes);

        if (! $this->hasValidDetailRows($codes, $sats, $qtys, $noacaks, $descs, $ketdts)) {
            return back()->withInput()
                ->withErrors(['detail' => 'Minimal satu item detail dengan Kode, Satuan, dan Qty ≥ 1.']);
        }

        DB::transaction(function () use (
            $request,
            $fprno,
            $fprdate,
            $fneeddate,
            $fduedate,
            $userName,
            $codes,
            $sats,
            $qtys,
            $noacaks,
            $descs,
            $ketdts,
            $productMap
        ) {
            $isApproval = $this->canApprovePurchaseRequest() ? (int) ($request->input('fapproval', 0)) : 0;

            $tr_prh = Tr_prh::create([
                'fprno' => $fprno,
                'fprdate' => $fprdate,
                'fsupplier' => $request->fsupplier,
                'fprdin' => '0',
                'fclose' => '0',
                'fket' => $request->fket,
                'fbranchcode' => $request->fbranchcode,
                'fcreatedat' => now(),
                'fneeddate' => $fneeddate,
                'fduedate' => $fduedate,
                'fusercreate' => $userName,
                'fuserapproved' => $request->has('fuserapproved') ? $userName : null,
                'fdateapproved' => $request->has('fuserapproved') ? now() : null,
                'fupdatedat' => null,
                'fapproval' => $isApproval,
            ]);

            $detailRows = [];
            $now = now();
            $rowCount = max(count($codes), count($sats), count($qtys), count($noacaks), count($descs), count($ketdts));
            $usedNoAcaks = [];

            for ($i = 0; $i < $rowCount; $i++) {
                $code = trim($codes[$i] ?? '');
                $sat = trim($sats[$i] ?? '');
                $qty = is_numeric($qtys[$i] ?? null) ? (float) $qtys[$i] : null;
                $noacak = $this->normalizeRandomNumber($noacaks[$i] ?? null, $usedNoAcaks);
                $desc = $descs[$i] ?? null;
                $ketdt = $ketdts[$i] ?? null;

                if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty > 0) {
                    $product = $productMap[$code] ?? null;
                    $productId = (int) ($product->fprdid ?? 0);
                    if ($productId === 0) {
                        continue;
                    }

                    $qtyKecil = $this->convertQtyToSmallUnit($product, $sat, $qty);

                    $detailRows[] = [
                        'fprdcode' => $product->fprdcode ?? '',
                        'fqty' => (float) $qty,
                        'fqtykecil' => $qtyKecil,
                        'fqtyremain' => $qtyKecil,
                        'fnoacak' => $noacak,
                        'fprice' => 0,
                        'fketdt' => $ketdt,
                        'fcreatedat' => $now,
                        'fsatuan' => $sat,
                        'fdesc' => $desc,
                        'fusercreate' => $userName,
                        'fprno' => $tr_prh->fprno,
                    ];
                }
            }

            Tr_prd::insert($detailRows);

            if ($isApproval === 1) {
                $dt = Tr_prd::query()
                    ->leftJoin('msprd as p', 'p.fprdcode', '=', 'tr_prd.fprdcode')
                    ->where('tr_prd.fprno', $tr_prh->fprno)
                    ->orderBy('p.fprdname')
                    ->get([
                        'tr_prd.*',
                        'p.fprdname as product_name',
                        'p.fprdcode as product_code',
                        'p.fminstock as stock',
                    ]);

                $productNameList = $dt->pluck('product_name')->implode(', ');
                $approver = auth('sysuser')->user()->fname ?? $tr_prh->fusercreate ?? 'System';

                $approvalRecipients = $this->getApprovalRecipients();
                if ($approvalRecipients !== []) {
                    Mail::to($approvalRecipients[0])
                        ->cc(array_slice($approvalRecipients, 1))
                        ->send(new ApprovalEmail($tr_prh, $dt, $productNameList, $approver, 'Permintaan Pembelian (PR)'));
                }
            }
        });

        return redirect()->route('tr_prh.create')
            ->with('success', 'Permintaan pembelian berhasil disimpan.');
    }

    public function view(Request $request, $fprhid)
    {
        $tr_prh = $this->findPrWithSupplier($fprhid);
        $branchInfo = $this->getCurrentBranchInfo($tr_prh->fbranchcode ?? null);
        $pageData = $this->buildPrPageData($tr_prh, true);

        return view('tr_prh.edit', [
            'suppliers' => $pageData['suppliers'],
            'fcabang' => $branchInfo['fcabang'],
            'fbranchlabel' => $branchInfo['fbranchlabel'],
            'fbranchcode' => $branchInfo['fbranchcode'],
            'products' => $pageData['products'],
            'productMap' => $pageData['productMap'],
            'tr_prh' => $tr_prh,
            'savedItems' => $pageData['savedItems'],
            'blockedByPO' => $pageData['blockedByPO'],
            'existingPO' => $pageData['existingPO'],
            'usageLockMessage' => $pageData['blockedByPO'] ? $this->getUsageLockMessage($tr_prh) : null,
            'action' => 'view',
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function edit(Request $request, $fprhid)
    {
        $tr_prh = $this->findPrWithSupplier($fprhid);
        $branchInfo = $this->getCurrentBranchInfo($tr_prh->fbranchcode ?? null);
        $pageData = $this->buildPrPageData($tr_prh, true);

        if ($message = $this->getPostedPeriodLockMessage($tr_prh->fprdate, 'Data ini')) {
            return redirect()
                ->route('tr_prh.view', $tr_prh->fprhid)
                ->with('error', $message);
        }

        if ($pageData['blockedByPO']) {
            return redirect()
                ->route('tr_prh.view', $tr_prh->fprhid)
                ->with('error', $this->getUsageLockMessage($tr_prh));
        }

        return view('tr_prh.edit', [
            'suppliers' => $pageData['suppliers'],
            'fcabang' => $branchInfo['fcabang'],
            'fbranchlabel' => $branchInfo['fbranchlabel'],
            'fbranchcode' => $branchInfo['fbranchcode'],
            'products' => $pageData['products'],
            'productMap' => $pageData['productMap'],
            'tr_prh' => $tr_prh,
            'savedItems' => $pageData['savedItems'],
            'blockedByPO' => $pageData['blockedByPO'],
            'existingPO' => $pageData['existingPO'],
            'usageLockMessage' => $pageData['blockedByPO'] ? $this->getUsageLockMessage($tr_prh) : null,
            'action' => 'edit',
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function update(Request $request, int $fprhid)
    {
        $header = Tr_prh::where('fprhid', $fprhid)->firstOrFail();

        if ($message = $this->getPostedPeriodLockMessage($header->fprdate, 'Data ini')) {
            return redirect()->route('tr_prh.view', $header->fprhid)->with('error', $message);
        }

        $isCloseOnly = $request->boolean('close_only');
        $hasReference = DB::table('tr_pod')->where('frefdtno', $header->fprno)->exists();
        $canCloseReferencedPr = $isCloseOnly
            && $request->has('fclose')
            && $hasReference
            && trim((string) ($header->fprdin ?? '')) === '0';

        if ($message = $this->getUsageLockMessage($header)) {
            if ($canCloseReferencedPr) {
                $message = null;
            }
        }

        if (! empty($message)) {
            return redirect()->route('tr_prh.index')->with('error', $message);
        }

        if ($isCloseOnly) {
            if (! $canCloseReferencedPr) {
                return back()->withInput()->with('error', 'Status close PR tidak bisa diupdate. PR harus sudah direferensi PO dan FPRDIN = 0.');
            }

            Tr_prh::where('fprhid', $header->fprhid)->update([
                'fclose' => '1',
                'fuserupdate' => $this->getAuthenticatedUserName('system'),
                'fupdatedat' => now(),
            ]);

            return redirect()
                ->route('tr_prh.index')
                ->with('success', "Status close PR {$header->fprno} berhasil diupdate.");
        }

        $this->validateUpdateRequest($request);
        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

        $fprdate = $request->filled('fprdate')
            ? \Carbon\Carbon::parse($request->fprdate)->startOfDay()
            : $header->fprdate;
        $this->ensureCreateDateWithinEditPeriod($fprdate, $header->fprdate);

        $fneeddate = $request->filled('fneeddate')
            ? \Carbon\Carbon::parse($request->fneeddate)->startOfDay()
            : $header->fneeddate;

        $fduedate = $request->filled('fduedate')
            ? \Carbon\Carbon::parse($request->fduedate)->startOfDay()
            : $header->fduedate;

        [
            'codes' => $codes,
            'sats' => $sats,
            'qtys' => $qtys,
            'noacaks' => $noacaks,
            'descs' => $descs,
            'ketdts' => $ketdts,
            'idsIn' => $idsIn,
        ] = $this->getDetailInputArrays($request, true);
        $productMap = $this->getRequestedProductsByCode($codes);

        $oldDetails = DB::table('tr_prd')->where('fprno', $header->fprno)->get()->keyBy('fprdid');

        $poUsage = DB::table('tr_pod')
            ->whereIn('frefdtid', $oldDetails->keys())
            ->select('frefdtid', DB::raw('SUM(fqtykecil) as total_used'))
            ->groupBy('frefdtid')
            ->pluck('total_used', 'frefdtid');

        $errors = new \Illuminate\Support\MessageBag;

        foreach ($codes as $i => $codeStr) {
            $code = trim($codeStr);
            $qty = (float) ($qtys[$i] ?? 0);
            $did = (int) ($idsIn[$i] ?? 0);
            $sat = trim($sats[$i] ?? '');

            if ($did > 0 && isset($oldDetails[$did])) {
                $old = $oldDetails[$did];
                $used = (float) ($poUsage[$did] ?? 0);

                $product = $productMap[$code] ?? null;
                $qtyKecil = $this->convertQtyToSmallUnit($product, $sat, $qty);

                if ($used > 0) {
                    if (trim($old->fprdcode) !== $code) {
                        $errors->add("fitemcode.$i", "Produk \"$code\" tidak boleh diubah karena sudah ada PO terkait.");
                    }
                    if (trim($old->fsatuan) !== $sat) {
                        $errors->add("fsatuan.$i", 'Satuan tidak boleh diubah karena sudah ada PO terkait.');
                    }
                    if ($qtyKecil < $used) {
                        $errors->add("fqty.$i", "Qty tidak boleh kurang dari yang sudah diproses ke PO ($used).");
                    }
                }
            }

        }

        if ($errors->isNotEmpty()) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use (
            $request,
            $header,
            $fprdate,
            $fneeddate,
            $fduedate,
            $codes,
            $idsIn,
            $sats,
            $qtys,
            $noacaks,
            $descs,
            $ketdts,
            $productMap,
            $oldDetails

        ) {
            $now = now();
            $userName = $this->getAuthenticatedUserName('system');
            $usedNoAcaks = [];

            $approveNow = $this->canApprovePurchaseRequest() && $request->boolean('fapproval');
            $headerUpdate = [
                'fprdate' => $fprdate,
                'fsupplier' => $request->filled('fsupplier') ? trim((string) $request->fsupplier) : $header->fsupplier,
                'fket' => $request->fket,
                'fbranchcode' => $request->fbranchcode,
                'fneeddate' => $fneeddate,
                'fduedate' => $fduedate,
                'fuserupdate' => $userName,
                'fupdatedat' => $now,
                'fclose' => $request->has('fclose') ? '1' : (string) ($header->fclose ?? '0'),
            ];
            if ($approveNow && (empty($header->fuserapproved) && (int) $header->fapproval !== 1)) {
                $headerUpdate['fapproval'] = 1;
                $headerUpdate['fuserapproved'] = $userName;
                $headerUpdate['fdateapproved'] = $now;
            }
            Tr_prh::where('fprhid', $header->fprhid)->update($headerUpdate);

            $submittedIds = array_filter($idsIn);

            DB::table('tr_prd')
                ->where('fprno', $header->fprno)
                ->whereNotIn('fprdid', $submittedIds)
                ->delete();

            foreach ($codes as $i => $codeStr) {
                $code = trim($codeStr);
                if ($code === '') {
                    continue;
                }

                $did = (int) ($idsIn[$i] ?? 0);
                $sat = trim($sats[$i] ?? '');
                $qty = (float) ($qtys[$i] ?? 0);
                $noacak = $this->normalizeRandomNumber($noacaks[$i] ?? null, $usedNoAcaks);
                $desc = $descs[$i] ?? null;
                $ket = $ketdts[$i] ?? null;
                $product = $productMap[$code] ?? null;

                $qtyKecil = $this->convertQtyToSmallUnit($product, $sat, $qty);

                $data = [
                    'fprdcode' => $code,
                    'fqty' => $qty,
                    'fqtykecil' => $qtyKecil,
                    'fqtyremain' => $qtyKecil,
                    'fnoacak' => $noacak,
                    'fketdt' => $ket,
                    'fsatuan' => $sat,
                    'fdesc' => $desc,
                    'fuserupdate' => $userName,
                    'fupdatedat' => $now,
                    'fprno' => $header->fprno,
                ];

                if ($did > 0 && isset($oldDetails[$did])) {
                    DB::table('tr_prd')->where('fprdid', $did)->update($data);
                } else {
                    $data['fcreatedat'] = $now;
                    $data['fusercreate'] = $userName;
                    DB::table('tr_prd')->insert($data);
                }
            }
        });

        return redirect()
            ->route('tr_prh.index')
            ->with('success', 'Permintaan pembelian berhasil diupdate.');
    }

    public function delete(Request $request, $fprhid)
    {
        $tr_prh = $this->findPrWithSupplier($fprhid, true);
        $branchInfo = $this->getCurrentBranchInfo($tr_prh->fbranchcode ?? null);
        $pageData = $this->buildPrPageData($tr_prh, false);

        if ($message = $this->getPostedPeriodLockMessage($tr_prh->fprdate, 'Data ini')) {
            return redirect()
                ->route('tr_prh.view', $tr_prh->fprhid)
                ->with('error', $message);
        }

        if ($pageData['blockedByPO']) {
            return redirect()
                ->route('tr_prh.view', $tr_prh->fprhid)
                ->with('error', $this->getUsageLockMessage($tr_prh));
        }

        return view('tr_prh.edit', [
            'suppliers' => $pageData['suppliers'],
            'fcabang' => $branchInfo['fcabang'],
            'fbranchlabel' => $branchInfo['fbranchlabel'],
            'fbranchcode' => $branchInfo['fbranchcode'],
            'products' => $pageData['products'],
            'productMap' => $pageData['productMap'],
            'tr_prh' => $tr_prh,
            'savedItems' => $pageData['savedItems'],
            'existingPO' => $pageData['existingPO'],
            'blockedByPO' => $pageData['blockedByPO'],
            'usageLockMessage' => $this->getUsageLockMessage($tr_prh),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'action' => 'delete',
        ]);
    }

    public function destroy($fprhid)
    {
        try {
            $tr_prh = Tr_prh::findOrFail($fprhid);

            if ($message = $this->getPostedPeriodLockMessage($tr_prh->fprdate, 'Data ini')) {
                return redirect()->route('tr_prh.view', $tr_prh->fprhid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($tr_prh)) {
                return redirect()->route('tr_prh.index')->with('error', $message);
            }

            DB::transaction(function () use ($tr_prh) {
                DB::table('tr_prd')
                    ->where('fprno', $tr_prh->fprno)
                    ->delete();
                $tr_prh->delete();
            });

            return redirect()->route('tr_prh.index')->with('success', 'Permintaan pembelian '.$tr_prh->fprno.' berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('tr_prh.delete', $fprhid)->with('error', 'Permintaan pembelian belum bisa dihapus. Coba lagi.');
        }
    }

    private function getUsageLockMessage(Tr_prh $header): ?string
    {
        $usedBy = DB::table('tr_pod as pod')
            ->join('tr_poh as poh', 'poh.fpono', '=', 'pod.fpono')
            ->where('pod.frefdtno', $header->fprno)
            ->select('poh.fpono')
            ->distinct()
            ->orderBy('poh.fpono')
            ->pluck('poh.fpono');

        if ($usedBy->isEmpty()) {
            return null;
        }

        return "Information\nPermintaan ini tidak dapat di-Edit/Delete.\nMasih ada Referensi di Transaksi:\n" . $usedBy->implode(', ');
    }

    private function validateStoreRequest(Request $request): void
    {
        $request->validate([
            'fprdate' => ['nullable', 'date'],
            'fneeddate' => ['nullable', 'date'],
            'fduedate' => ['nullable', 'date'],
            'fket' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],

            'fitemcode' => ['array'],
            'fitemcode.*' => ['nullable', 'string', 'max:50'],

            'fsatuan' => ['array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],

            'fqty' => ['array'],
            'fqty.*' => ['nullable'],

            'fnoacak' => ['array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],

            'fdesc' => ['array'],
            'fdesc.*' => ['nullable', 'string'],

            'fketdt' => ['array'],
            'fketdt.*' => ['nullable', 'string', 'max:50'],

            'fapproval' => ['nullable'],
        ], [
            'fitemcode.*.max' => 'Panjang kode produk maksimal 50 karakter.',
            'fsatuan.*.max' => 'Panjang satuan maksimal 20 karakter.',
            'fnoacak.*.regex' => 'No acak harus terdiri dari 3 digit angka 1-9 tanpa 0.',
            'fdesc.*.max' => 'Panjang deskripsi maksimal 300 karakter.',
            'fketdt.*.max' => 'Panjang keterangan detail maksimal 50 karakter.',
        ]);
    }

    private function validateUpdateRequest(Request $request): void
    {
        $request->validate([
            'fprdate' => ['nullable', 'date'],
            'fneeddate' => ['nullable', 'date'],
            'fduedate' => ['nullable', 'date'],
            'fket' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],

            'fitemcode' => ['array'],
            'fitemcode.*' => ['nullable', 'string', 'max:50'],
            'fprdid' => ['array'],
            'fprdid.*' => ['nullable', 'integer', 'min:1'],
            'fsatuan' => ['array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],
            'fqty' => ['array'],
            'fqty.*' => ['nullable', 'numeric', 'min:0.01'],
            'fnoacak' => ['array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'fdesc' => ['array'],
            'fdesc.*' => ['nullable', 'string'],
            'fketdt' => ['array'],
            'fketdt.*' => ['nullable', 'string', 'max:50'],

            'fapproval' => ['nullable', 'boolean'],
        ], [
            'fitemcode.*.max' => 'Panjang kode produk maksimal 50 karakter.',
            'fprdid.*.integer' => 'ID produk tidak valid.',
            'fprdid.*.min' => 'ID produk harus lebih besar dari 0.',
            'fsatuan.*.max' => 'Panjang satuan maksimal 20 karakter.',
            'fnoacak.*.regex' => 'No acak harus terdiri dari 3 digit angka 1-9 tanpa 0.',
            'fdesc.*.max' => 'Panjang deskripsi maksimal 300 karakter.',
            'fketdt.*.max' => 'Panjang keterangan detail maksimal 50 karakter.',
        ]);
    }

    private function getDetailInputArrays(Request $request, bool $includeIds = false): array
    {
        $detailInputs = [
            'codes' => $request->input('fitemcode', []),
            'sats' => $request->input('fsatuan', []),
            'qtys' => $request->input('fqty', []),
            'noacaks' => $request->input('fnoacak', []),
            'descs' => $request->input('fdesc', []),
            'ketdts' => $request->input('fketdt', []),
        ];

        if ($includeIds) {
            $detailInputs['idsIn'] = $request->input('fprdid', []);
        }

        return $detailInputs;
    }

    private function getRequestedProductsByCode(array $codes)
    {
        return DB::table('msprd')
            ->whereIn('fprdcode', array_values(array_filter($codes)))
            ->select(
                'fprdid',
                'fprdcode',
                'fminstock',
                'fsatuandefault',
                'fsatuankecil',
                'fsatuanbesar',
                'fqtykecil',
                'fsatuanbesar2',
                'fqtykecil2',
            )
            ->get()
            ->keyBy('fprdcode');
    }

    private function hasValidDetailRows(array $codes, array $sats, array $qtys, array $noacaks, array $descs, array $ketdts): bool
    {
        $rowCount = max(count($codes), count($sats), count($qtys), count($noacaks), count($descs), count($ketdts));

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim($codes[$i] ?? '');
            $sat = trim($sats[$i] ?? '');
            $qty = is_numeric($qtys[$i] ?? null) ? (float) $qtys[$i] : null;

            if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty > 0) {
                return true;
            }
        }

        return false;
    }

    private function ensureNoDuplicateDetailCodes(array $codes): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($codes as $index => $rawCode) {
            $code = strtoupper(trim((string) $rawCode));
            if ($code === '') {
                continue;
            }

            if (isset($seen[$code])) {
                $duplicates[$index] = $code;
                continue;
            }

            $seen[$code] = true;
        }

        if ($duplicates === []) {
            return;
        }

        $messages = [];
        foreach ($duplicates as $index => $code) {
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Permintaan Pembelian.";
        }

        throw ValidationException::withMessages($messages);
    }

    private function findPrWithSupplier($fprhid, bool $includeSupplierCode = false)
    {
        $selectColumns = ['tr_prh.*', 's.fsuppliername', 's.fsuppliercode'];

        if ($includeSupplierCode) {
            $selectColumns[] = 's.fsuppliercode';
        }

        return Tr_prh::with(['details' => function ($q) {
            $q->leftJoin('msprd as p', 'p.fprdcode', '=', 'tr_prd.fprdcode')
                ->orderBy('p.fprdname')
                ->select(
                    'tr_prd.*',
                    'p.fprdcode as product_code',
                    'p.fprdname as product_name'
                );
        }])
            ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'tr_prh.fsupplier')
            ->select(...$selectColumns)
            ->findOrFail($fprhid);
    }

    private function buildPrPageData(Tr_prh $tr_prh, bool $includePricing = false): array
    {
        $suppliers = $this->getSuppliers();
        $products = $this->getProducts();
        $details = $this->getPrDetailsWithPoUsage($tr_prh->fprno);
        $existingPO = $this->getExistingPurchaseOrders($tr_prh->fprno);

        return [
            'suppliers' => $suppliers,
            'products' => $products,
            'productMap' => $this->buildProductMap($products),
            'savedItems' => $this->buildSavedItems($details, $includePricing),
            'existingPO' => $existingPO,
            'blockedByPO' => $existingPO->isNotEmpty(),
        ];
    }

    private function getAuthenticatedUserName(?string $default = null): ?string
    {
        return auth('sysuser')->user()->fname ?? Auth::user()->fname ?? $default;
    }

    private function getSuppliers()
    {
        return Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername']);
    }

    private function getProducts()
    {
        return Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuandefault',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )
            ->orderBy('fprdname')
            ->get();
    }

    private function buildProductMap($products): array
    {
        return $products->mapWithKeys(function ($product) {
            return [
                trim($product->fprdcode) => [
                    'id' => $product->fprdid ?? null,
                    'name' => $product->fprdname,
                    'default_unit' => $this->resolveProductDefaultUnit($product),
                    'units' => array_values(array_filter([
                        $product->fsatuankecil,
                        $product->fsatuanbesar,
                        $product->fsatuanbesar2,
                    ])),
                    'stock' => $product->fminstock ?? 0,
                    'unit_ratios' => [
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($product->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($product->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();
    }

    private function getCurrentBranchInfo($branchCode = null): array
    {
        $context = $this->resolveBranchContext($branchCode);

        return [
            'raw' => $context['fbranchcode'],
            'branch' => null,
            'fcabang' => $context['fcabang'],
            'fbranchcode' => $context['fbranchcode'],
            'fbranchlabel' => trim(implode(' - ', array_filter([
                trim((string) ($context['fbranchcode'] ?? '')),
                trim((string) ($context['fcabang'] ?? '')),
            ]))) ?: (string) ($context['fbranchcode'] ?? ''),
        ];
    }

    private function getExistingPurchaseOrders(string $fprno)
    {
        return DB::table('tr_pod as pod')
            ->join('tr_poh as poh', 'poh.fpono', '=', 'pod.fpono')
            ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'poh.fsupplier')
            ->where('pod.frefdtno', $fprno)
            ->select('poh.fpono', 'poh.fpodate', 's.fsuppliername')
            ->distinct()
            ->orderBy('poh.fpodate', 'desc')
            ->get();
    }

    private function buildSavedItems($details, bool $includePricing = false)
    {
        return $details->map(function ($detail) use ($includePricing) {
            $existingUnit = trim((string) ($detail->fsatuan ?? ''));
            $units = array_values(array_unique(array_filter(array_map('trim', [
                $existingUnit,
                $detail->fsatuankecil ?? '',
                $detail->fsatuanbesar ?? '',
                $detail->fsatuanbesar2 ?? '',
            ]))));

            $item = [
                'uid' => (string) \Illuminate\Support\Str::uuid(),
                'fitemcode' => (string) ($detail->fprdcode_master ?? ''),
                'fitemname' => (string) ($detail->fprdname ?? ''),
                'fsatuan' => $existingUnit,
                'units' => $units,
                'fqty' => (float) ($detail->fqty ?? 0),
                'fqtypo' => (float) ($detail->fqtydipo ?? $detail->fqtypo ?? 0),
                'fqtysisapr' => (float) ($detail->fqtysisapr ?? 0),
                'fqtydipo' => (float) ($detail->fqtydipo ?? 0),
                'fnoacak' => (string) ($detail->fnoacak ?? ''),
                'fdesc' => (string) ($detail->fdesc ?? ''),
                'fketdt' => (string) ($detail->fketdt ?? ''),
            ];

            if ($includePricing) {
                $item['fprice'] = (float) ($detail->fprice ?? 0);
                $item['fdisc'] = (float) ($detail->fdisc ?? 0);
            }

            return $item;
        })->values();
    }

    private function convertQtyToSmallUnit($product, string $unit, float $qty): float|int
    {
        if (! $product) {
            return $qty;
        }

        if ($unit === $product->fsatuanbesar) {
            $ratio = is_numeric($product->fqtykecil) ? (float) $product->fqtykecil : 1;

            return $qty * $ratio;
        }

        if (! empty($product->fsatuanbesar2) && $unit === $product->fsatuanbesar2) {
            $ratio = is_numeric($product->fqtykecil2) ? (float) $product->fqtykecil2 : 1;

            return $qty * $ratio;
        }

        return $qty;
    }

    private function normalizeRandomNumber($value, array &$usedNumbers): string
    {
        $value = trim((string) ($value ?? ''));
        $candidate = preg_match('/^[1-9]{3}$/', $value) ? $value : null;

        if ($candidate !== null && ! in_array($candidate, $usedNumbers, true)) {
            $usedNumbers[] = $candidate;

            return $candidate;
        }

        do {
            $candidate = (string) random_int(1, 9).random_int(1, 9).random_int(1, 9);
        } while (in_array($candidate, $usedNumbers, true));

        $usedNumbers[] = $candidate;

        return $candidate;
    }

    private function getPrDetailsWithPoUsage(string $fprno)
    {
        return DB::table('tr_prd as d')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
            ->leftJoin(
                DB::raw('(
                    SELECT frefdtno, fprdcode, frefnoacak, SUM(fqtykecil) AS fqtykecilpo
                    FROM tr_pod
                    GROUP BY frefdtno, fprdcode, frefnoacak
                ) as po'),
                function ($join) {
                    $join->on('po.frefdtno', '=', 'd.fprno')
                        ->on('po.fprdcode', '=', 'd.fprdcode')
                        ->on('po.frefnoacak', '=', 'd.fnoacak');
                }
            )
            ->where('d.fprno', $fprno)
            ->select([
                'd.*',
                'p.fprdname',
                'p.fprdcode as fprdcode_master',
                'p.fsatuankecil',
                'p.fsatuanbesar',
                'p.fsatuanbesar2',
                DB::raw('COALESCE(
                    CASE 
                        WHEN d.fsatuan=p.fsatuanbesar 
                            THEN (coalesce(fqtykecilpo,0))/p.fqtykecil
                        WHEN d.fsatuan=p.fsatuanbesar2 
                            THEN (coalesce(fqtykecilpo,0))/p.fqtykecil2
                        ELSE coalesce(fqtykecilpo,0) END,0) AS fqtypo'),
                DB::raw('COALESCE(
                    CASE
                        WHEN d.fsatuan = p.fsatuanbesar
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(fqtykecilpo, 0)) / NULLIF(p.fqtykecil, 0)
                        WHEN d.fsatuan = p.fsatuanbesar2
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(fqtykecilpo, 0)) / NULLIF(p.fqtykecil2, 0)
                        ELSE COALESCE(d.fqtykecil, 0) - COALESCE(fqtykecilpo, 0)
                    END, 0) AS fqtysisapr'),
                DB::raw('COALESCE(
                    CASE
                        WHEN d.fsatuan = p.fsatuanbesar
                            THEN COALESCE(fqtykecilpo, 0) / NULLIF(p.fqtykecil, 0)
                        WHEN d.fsatuan = p.fsatuanbesar2
                            THEN COALESCE(fqtykecilpo, 0) / NULLIF(p.fqtykecil2, 0)
                        ELSE COALESCE(fqtykecilpo, 0)
                    END, 0) AS fqtydipo'),
            ])
            ->get();
    }
}
