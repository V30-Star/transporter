<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LembarPenagihanController extends Controller
{
    private const CODE = 'LPT';
    private const DAILY_CREATE_LIMIT = 15;

    private function todayCreateCount(): int
    {
        return DB::table('trtagihanmt')
            ->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function hasReachedDailyCreateLimit(): bool
    {
        return $this->todayCreateCount() >= self::DAILY_CREATE_LIMIT;
    }

    public function index(Request $request)
    {
        $canCreate = $this->hasRestrictedPermission('createInvoice');
        $canEdit = $this->hasRestrictedPermission('updateInvoice');
        $canDelete = $this->hasRestrictedPermission('deleteInvoice');
        $showActionsColumn = $canEdit || $canDelete;

        if ($request->ajax()) {
            $query = DB::table('trtagihanmt as h')
                ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fcustno')
                ->leftJoin('trtagihandt as d', 'd.ftagihanno', '=', 'h.ftagihanno')
                ->selectRaw("\n                    h.ftagihanid,\n                    h.ftagihanno,\n                    h.ftagihandate,\n                    h.fbranchcode,\n                    h.fcustno,\n                    c.fcustomername,\n                    h.famounttagihan,\n                    h.fnote,\n                    STRING_AGG(TRIM(d.frefsono), ', ' ORDER BY d.frefsono) as invoice_refs\n                ")
                ->groupBy('h.ftagihanid', 'h.fbranchcode', 'h.ftagihanno', 'h.ftagihandate', 'h.fcustno', 'c.fcustomername', 'h.famounttagihan', 'h.fnote');

            $totalRecords = DB::query()->fromSub(clone $query, 'tagihan_rows')->count();
            if ($search = trim((string) $request->input('search.value', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('h.ftagihanno', 'ilike', "%{$search}%")
                        ->orWhere('c.fcustomername', 'ilike', "%{$search}%")
                        ->orWhere('h.fnote', 'ilike', "%{$search}%")
                        ->orWhere('d.frefsono', 'ilike', "%{$search}%");
                });
            }
            $filteredRecords = DB::query()->fromSub(clone $query, 'tagihan_rows')->count();

            $records = $query
                ->orderBy('h.ftagihandate', 'desc')
                ->orderBy('h.ftagihanno', 'desc')
                ->skip((int) $request->input('start', 0))
                ->take((int) $request->input('length', 10))
                ->get();

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $records->map(fn ($row) => [
                    'ftagihanid' => $row->ftagihanid,
                    'ftagihanno' => trim((string) $row->ftagihanno),
                    'ftagihandate' => $row->ftagihandate ? Carbon::parse($row->ftagihandate)->format('d-m-Y') : '',
                    'fcabang' => trim((string) $row->fbranchcode),
                    'invoice_refs' => trim((string) $row->invoice_refs),
                    'fcustomername' => trim((string) $row->fcustomername),
                    'famounttagihan' => (float) $row->famounttagihan,
                    'fnote' => trim((string) $row->fnote),
                    'actions' => view('lembarpenagihan.partials.actions', [
                        'row' => $row,
                        'canEdit' => $canEdit,
                        'canDelete' => $canDelete,
                    ])->render(),
                ]),
            ]);
        }

        $createLimitReached = $this->hasReachedDailyCreateLimit();

        return view('lembarpenagihan.index', compact('canCreate', 'canEdit', 'canDelete', 'showActionsColumn', 'createLimitReached'));
    }

    public function create()
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('lembarpenagihan.index')
                ->with('create_limit_exceeded', true);
        }

        return view('lembarpenagihan.create', $this->formData());
    }

    public function pickableInvoices(Request $request)
    {
        $customerCode = trim((string) $request->input('fcustno', $request->input('customer_code', '')));
        $search = trim((string) $request->input('search', ''));

        $query = DB::table('tranmt as i')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'i.fcustno')
            ->where('i.ftrcode', 'INV')
            ->whereRaw('COALESCE(i.famountremain, i.famountso, 0) <> 0')
            ->when($customerCode !== '', fn ($q) => $q->where('i.fcustno', $customerCode))
            ->select([
                'i.fsono',
                'i.frefno',
                'i.fsodate',
                'i.fcustno',
                'c.fcustomername',
                DB::raw('COALESCE(i.famountso, 0) as famountbil'),
                DB::raw('COALESCE(i.fongkosangkut, 0) as fongkos'),
                DB::raw('COALESCE(i.famountremain, i.famountso, 0) as famount'),
            ]);

        $recordsTotal = DB::table('tranmt as i')
            ->where('i.ftrcode', 'INV')
            ->whereRaw('COALESCE(i.famountremain, i.famountso, 0) <> 0')
            ->when($customerCode !== '', fn ($q) => $q->where('i.fcustno', $customerCode))
            ->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('i.fsono', 'ilike', "%{$search}%")
                    ->orWhere('i.frefno', 'ilike', "%{$search}%")
                    ->orWhere('i.fcustno', 'ilike', "%{$search}%")
                    ->orWhere('c.fcustomername', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumn = (string) $request->input('order_column', 'fsodate');
        $orderDir = strtolower((string) $request->input('order_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedColumns = ['fsono', 'frefno', 'fsodate', 'fcustno', 'fcustomername', 'famountbil', 'fongkos', 'famount'];
        if (! in_array($orderColumn, $allowedColumns, true)) {
            $orderColumn = 'fsodate';
        }

        if ($orderColumn === 'fcustomername') {
            $query->orderBy('c.fcustomername', $orderDir);
        } elseif (in_array($orderColumn, ['famountbil', 'fongkos', 'famount'], true)) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('i.' . $orderColumn, $orderDir);
        }

        $data = $query
            ->orderBy('i.fsono', 'desc')
            ->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 10))
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function pickableReturns(Request $request)
    {
        $customerCode = trim((string) $request->input('fcustno', $request->input('customer_code', '')));
        $search = trim((string) $request->input('search', ''));

        $query = DB::table('tranmt as r')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'r.fcustno')
            ->where('r.ftrcode', 'REJ')
            ->whereRaw('COALESCE(r.famountremain, r.famountso, 0) <> 0')
            ->when($customerCode !== '', fn ($q) => $q->where('r.fcustno', $customerCode))
            ->select([
                'r.fsono',
                'r.frefno',
                'r.fsodate',
                'r.fcustno',
                'c.fcustomername',
                DB::raw('COALESCE(r.famountso, 0) as famountbil'),
                DB::raw('COALESCE(r.fongkosangkut, 0) as fongkos'),
                DB::raw('COALESCE(r.famountremain, r.famountso, 0) as famount'),
            ]);

        $recordsTotal = DB::table('tranmt as r')
            ->where('r.ftrcode', 'REJ')
            ->whereRaw('COALESCE(r.famountremain, r.famountso, 0) <> 0')
            ->when($customerCode !== '', fn ($q) => $q->where('r.fcustno', $customerCode))
            ->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('r.fsono', 'ilike', "%{$search}%")
                    ->orWhere('r.frefno', 'ilike', "%{$search}%")
                    ->orWhere('r.fcustno', 'ilike', "%{$search}%")
                    ->orWhere('c.fcustomername', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();
        $data = $query
            ->orderBy('r.fsodate', 'desc')
            ->orderBy('r.fsono', 'desc')
            ->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 10))
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Batas membuat data sudah terlampaui.',
                    'redirect_url' => route('lembarpenagihan.index'),
                ], 422);
            }

            return redirect()
                ->route('lembarpenagihan.index')
                ->with('create_limit_exceeded', true);
        }

        $data = $this->validatedData($request);
        $tagihanNo = trim((string) ($data['ftagihanno'] ?? '')) ?: $this->generateTagihanNo(Carbon::parse($data['ftagihandate']));
        $total = array_sum(array_map('floatval', $data['famount']));
        $userId = substr((string) (auth()->user()->fname ?? auth()->user()->name ?? 'SYSTEM'), 0, 10);

        DB::transaction(function () use ($data, $tagihanNo, $total, $userId) {
            $branch = auth()->guard('sysuser')->user()?->fcabang
                ?? auth()->user()?->fcabang
                ?? null;

            $kodeCabang = null;
            if ($branch !== null) {
                $needle = trim((string) $branch);
                if (is_numeric($needle)) {
                    $kodeCabang = DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode');
                } else {
                    $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
                        ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
                }
            }
            if (! $kodeCabang) {
                $kodeCabang = 'NA';
            }

            DB::table('trtagihanmt')->insert([
                'ftagihanno' => $tagihanNo,
                'ftagihandate' => $data['ftagihandate'],
                'fcustno' => $data['fcustno'],
                'ftrancode' => self::CODE,
                'fnote' => $data['fnote'] ?? null,
                'famounttagihan' => $total,
                'fuserid' => $userId,
                'fdatetime' => now(),
                'fbranchcode' => $kodeCabang,
            ]);
            $this->replaceDetails($tagihanNo, $data, $userId);
        });

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Lembar penagihan berhasil disimpan.',
                'redirect_url' => route('lembarpenagihan.index'),
            ]);
        }

        return redirect()->route('lembarpenagihan.index')->with('success', 'Lembar penagihan berhasil disimpan.');
    }

    public function edit(int $id)
    {
        return view('lembarpenagihan.edit', $this->formData($id, 'edit'));
    }

    public function view(int $id)
    {
        return view('lembarpenagihan.view', $this->formData($id, 'view'));
    }

    public function delete(int $id)
    {
        return view('lembarpenagihan.delete', $this->formData($id, 'delete'));
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validatedData($request, $id);
        $header = $this->headerQuery()->where('h.ftagihanid', $id)->firstOrFail();
        $tagihanNo = trim((string) $header->ftagihanno);
        $total = array_sum(array_map('floatval', $data['famount']));
        
        $userLogin = auth('sysuser')->user() ?? auth()->user();
        $userId = substr((string) ($userLogin->fname ?? $userLogin->name ?? 'SYSTEM'), 0, 10);
        $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'SYSTEM';

        DB::transaction(function () use ($data, $id, $tagihanNo, $total, $userId, $userIdLog) {
            $now = now();
            $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

            $branch = auth()->guard('sysuser')->user()?->fcabang
                ?? auth()->user()?->fcabang
                ?? null;

            $kodeCabang = null;
            if ($branch !== null) {
                $needle = trim((string) $branch);
                if (is_numeric($needle)) {
                    $kodeCabang = DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode');
                } else {
                    $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
                        ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
                }
            }
            if (! $kodeCabang) {
                $kodeCabang = 'NA';
            }

            // 1. Update Header Utama
            DB::table('trtagihanmt')->where('ftagihanid', $id)->update([
                'ftagihandate'   => $data['ftagihandate'],
                'fcustno'        => $data['fcustno'],
                'ftrancode'      => self::CODE,
                'fnote'          => $data['fnote'] ?? null,
                'famounttagihan' => $total,
                'fuserid'        => $userId,
                'fdatetime'      => $now,
                'fbranchcode'    => $kodeCabang,
            ]);

            $updatedHeader = DB::table('trtagihanmt')->where('ftagihanid', $id)->first();

            // 2. INSERT Log Header (Update)
            DB::table('log_trtagihanmt')->insert([
                'ftrxlogid'      => $trxLogId,
                'ftagihanid'     => $updatedHeader->ftagihanid,
                'ftagihanno'     => $updatedHeader->ftagihanno,
                'fbranchcode'    => $updatedHeader->fbranchcode,
                'fcustno'        => $updatedHeader->fcustno,
                'ftrancode'      => $updatedHeader->ftrancode,
                'ftagihandate'   => $updatedHeader->ftagihandate,
                'fnote'          => $updatedHeader->fnote,
                'famounttagihan' => $updatedHeader->famounttagihan,
                'fprint'         => $updatedHeader->fprint,
                'fdatetime'      => $updatedHeader->fdatetime,
                'fuserid'        => $updatedHeader->fuserid,
                'feditmode'      => 'U',
                'fuseridlog'     => $userIdLog,
                'fdatetimelog'   => $now,
            ]);

            // 3. Delete Detail Lama
            DB::table('trtagihandt')->where('ftagihanno', $tagihanNo)->delete();

            // 4. Insert Detail Baru & Log Detail
            $this->replaceDetailsWithLog($tagihanNo, $data, $userId, $trxLogId, 'U', $userIdLog, $now);
        });

        if (request()->expectsJson()) {
            return response()->json([
                'message'      => 'Lembar penagihan berhasil diupdate.',
                'redirect_url' => route('lembarpenagihan.index'),
            ]);
        }

        return redirect()->route('lembarpenagihan.index')->with('success', 'Lembar penagihan berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $header = $this->headerQuery()->where('h.ftagihanid', $id)->firstOrFail();

        $userLogin = auth('sysuser')->user() ?? auth()->user();
        $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'SYSTEM';

        DB::transaction(function () use ($header, $id, $userIdLog) {
            $now = now();
            $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

            // 1. Log Header (Delete)
            DB::table('log_trtagihanmt')->insert([
                'ftrxlogid'      => $trxLogId,
                'ftagihanid'     => $header->ftagihanid,
                'ftagihanno'     => $header->ftagihanno,
                'fbranchcode'    => $header->fbranchcode ?? null,
                'fcustno'        => $header->fcustno ?? null,
                'ftrancode'      => $header->ftrancode ?? null,
                'ftagihandate'   => $header->ftagihandate ?? null,
                'fnote'          => $header->fnote ?? null,
                'famounttagihan' => $header->famounttagihan ?? null,
                'fprint'         => $header->fprint ?? null,
                'fdatetime'      => $header->fdatetime ?? null,
                'fuserid'        => $header->fuserid ?? null,
                'feditmode'      => 'D',
                'fuseridlog'     => $userIdLog,
                'fdatetimelog'   => $now,
            ]);

            // 2. Ambil & Log Detail (Delete)
            $details = DB::table('trtagihandt')->where('ftagihanno', $header->ftagihanno)->get();
            foreach ($details as $dt) {
                DB::table('log_trtagihandt')->insert([
                    'ftrxlogid'   => $trxLogId,
                    'ftrtagihanid'=> (int) $dt->ftrtagihanid,
                    'ftrancode'   => $dt->ftrancode,
                    'frefcode'    => $dt->frefcode,
                    'ftagihanno'  => $dt->ftagihanno,
                    'frefsono'    => $dt->frefsono,
                    'famount'     => $dt->famount,
                    'fdatetime'   => $dt->fdatetime,
                    'fuserid'     => $dt->fuserid,
                    'feditmode'   => 'D',
                    'fuseridlog'  => $userIdLog,
                    'fdatetimelog'=> $now,
                ]);
            }

            // 3. Delete Detail & Header
            DB::table('trtagihandt')->where('ftagihanno', $header->ftagihanno)->delete();
            DB::table('trtagihanmt')->where('ftagihanid', $id)->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'message'      => 'Lembar penagihan berhasil dihapus.',
                'redirect_url' => route('lembarpenagihan.index'),
            ]);
        }

        return redirect()->route('lembarpenagihan.index')->with('success', 'Lembar penagihan berhasil dihapus.');
    }

    /**
     * Helper internal untuk insert detail dan mencatat ke log_trtagihandt
     */
    private function replaceDetailsWithLog($tagihanNo, array $data, string $userId, string $trxLogId, string $editMode, string $userIdLog, $now)
    {
        foreach ($data['frefsono'] as $i => $refSono) {
            $amount = (float) ($data['famount'][$i] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $refCode = $data['frefcode'][$i] ?? 'INV';

            // Insert ke trtagihandt
            $insertedId = DB::table('trtagihandt')->insertGetId([
                'ftrancode'  => self::CODE,
                'frefcode'   => $refCode,
                'ftagihanno' => $tagihanNo,
                'frefsono'   => $refSono,
                'famount'    => $amount,
                'fdatetime'  => $now,
                'fuserid'    => $userId,
            ], 'ftrtagihanid');

            $dtObj = DB::table('trtagihandt')->where('ftrtagihanid', $insertedId)->first();

            // Insert ke log_trtagihandt
            DB::table('log_trtagihandt')->insert([
                'ftrxlogid'   => $trxLogId,
                'ftrtagihanid'=> (int) $dtObj->ftrtagihanid,
                'ftrancode'   => $dtObj->ftrancode,
                'frefcode'    => $dtObj->frefcode,
                'ftagihanno'  => $dtObj->ftagihanno,
                'frefsono'    => $dtObj->frefsono,
                'famount'     => $dtObj->famount,
                'fdatetime'   => $dtObj->fdatetime,
                'fuserid'     => $dtObj->fuserid,
                'feditmode'   => $editMode,
                'fuseridlog'  => $userIdLog,
                'fdatetimelog'=> $now,
            ]);
        }
    }

    public function print(string $ftagihanno)
    {
        $hdr = DB::table('trtagihanmt as h')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fcustno')
            ->leftJoin('mscabang as b', 'b.fcabangkode', '=', 'h.fbranchcode')
            ->where('h.ftagihanno', $ftagihanno)
            ->first([
                'h.*',
                'c.fcustomername as customer_name',
                'c.faddress as customer_address',
                'b.fcabangname as cabang_name',
            ]);

        if (!$hdr) {
            return redirect()->back()->with('error', 'Lembar penagihan tidak ditemukan.');
        }

        DB::table('trtagihanmt')->where('ftagihanno', $hdr->ftagihanno)->update(['fprint' => 1]);

        if (empty($hdr->cabang_name)) {
            $firstRef = DB::table('trtagihandt as d')
                ->leftJoin('tranmt as i', 'i.fsono', '=', 'd.frefsono')
                ->leftJoin('mscabang as cb', 'cb.fcabangkode', '=', 'i.fbranchcode')
                ->where('d.ftagihanno', $ftagihanno)
                ->whereNotNull('i.fbranchcode')
                ->first(['i.fbranchcode', 'cb.fcabangname as cabang_name']);

            if ($firstRef) {
                $hdr->fbranchcode = $firstRef->fbranchcode;
                $hdr->cabang_name = $firstRef->cabang_name;
            }
        }

        $dt = DB::table('trtagihandt as d')
            ->leftJoin('tranmt as i', 'i.fsono', '=', 'd.frefsono')
            ->where('d.ftagihanno', $ftagihanno)
            ->orderBy('d.ftrtagihanid', 'asc')
            ->get([
                'd.*',
                'i.fsodate',
                DB::raw('COALESCE(i.famountso, ABS(d.famount)) as famountbil'),
                DB::raw('COALESCE(i.fongkosangkut, 0) as fongkos'),
            ]);

        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('lembarpenagihan.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    private function formData(?int $id = null, string $action = 'create'): array
    {
        $header = $id ? $this->headerQuery()->where('h.ftagihanid', $id)->firstOrFail() : null;
        $details = $header ? $this->details($header->ftagihanno) : collect();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);

        return [
            'header' => $header,
            'details' => $details,
            'customers' => $customers,
            'action' => $action,
            'nextNo' => $header?->ftagihanno ?? $this->generateTagihanNo(now()),
        ];
    }

    private function headerQuery()
    {
        return DB::table('trtagihanmt as h')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fcustno')
            ->selectRaw('h.ftagihanid, h.ftagihanno, h.ftagihandate, h.fcustno, c.fcustomername, h.famounttagihan, h.fnote, h.fdatetime, h.fuserid');
    }

    private function details(string $tagihanNo)
    {
        return DB::table('trtagihandt as d')
            ->leftJoin('tranmt as i', 'i.fsono', '=', 'd.frefsono')
            ->where('d.ftagihanno', $tagihanNo)
            ->orderBy('d.ftrtagihanid')
            ->get([
                'd.ftrtagihanid',
                'd.frefcode',
                'd.frefsono',
                'd.famount',
                'i.fsodate',
                DB::raw('COALESCE(i.famountso, ABS(d.famount)) as famountbil'),
                DB::raw('COALESCE(i.fongkosangkut, 0) as fongkos'),
            ]);
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'ftagihanno' => [
                'nullable',
                'string',
                'max:30',
                $ignoreId
                    ? 'unique:trtagihanmt,ftagihanno,' . $ignoreId . ',ftagihanid'
                    : 'unique:trtagihanmt,ftagihanno'
            ],
            'fcustno' => ['required', 'string', 'max:10'],
            'ftagihandate' => ['required', 'date'],
            'fnote' => ['nullable', 'string'],
            'frefsono' => ['required', 'array', 'min:1'],
            'frefsono.*' => ['required', 'string', 'max:20'],
            'frefcode' => ['required', 'array'],
            'frefcode.*' => ['required', 'string', 'max:3'],
            'famount' => ['required', 'array'],
            'famount.*' => ['required', 'numeric'],
        ]);
    }

    private function replaceDetails(string $tagihanNo, array $data, string $userId): void
    {
        foreach ($data['frefsono'] as $idx => $refNo) {
            DB::table('trtagihandt')->insert([
                'ftrancode' => self::CODE,
                'frefcode' => substr((string) ($data['frefcode'][$idx] ?? 'INV'), 0, 3),
                'ftagihanno' => $tagihanNo,
                'frefsono' => substr((string) $refNo, 0, 20),
                'famount' => (float) ($data['famount'][$idx] ?? 0),
                'fdatetime' => now(),
                'fuserid' => $userId,
            ]);
        }
    }

    private function generateTagihanNo(Carbon $date): string
    {
        $branch = auth()->guard('sysuser')->user()?->fcabang
            ?? auth()->user()?->fcabang
            ?? null;

        $kodeCabang = null;
        if ($branch !== null) {
            $needle = trim((string) $branch);
            if (is_numeric($needle)) {
                $kodeCabang = DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode');
            } else {
                $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
                    ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
            }
        }
        if (! $kodeCabang) {
            $kodeCabang = 'NA';
        }

        $prefix = sprintf('LPT.%s.%s%s.', $kodeCabang, $date->format('y'), $date->format('m'));
        $last = DB::table('trtagihanmt')
            ->where('ftagihanno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(ftagihanno, '.', 4) AS int)) AS lastno")
            ->value('lastno');
        $next = (int) $last + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
