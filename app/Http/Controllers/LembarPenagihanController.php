<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LembarPenagihanController extends Controller
{
    private const CODE = 'TAG';

    public function index(Request $request)
    {
        $canCreate = $this->hasRestrictedPermission('createInvoice');
        $canEdit = $this->hasRestrictedPermission('updateInvoice');
        $canDelete = $this->hasRestrictedPermission('deleteInvoice');
        $showActionsColumn = $canEdit || $canDelete;

        if ($request->ajax()) {
            $query = DB::table('trstockmt as h')
                ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fsupplier')
                ->leftJoin('trtagihandt as d', 'd.ftagihanno', '=', 'h.fstockmtno')
                ->where('h.fstockmtcode', self::CODE)
                ->selectRaw("\n                    h.fstockmtid,\n                    h.fstockmtno as ftagihanno,\n                    h.fstockmtdate,\n                    h.fsupplier as fcustno,\n                    c.fcustomername,\n                    h.famount as famounttagihan,\n                    h.fket as fnote,\n                    STRING_AGG(TRIM(d.frefsono), ', ' ORDER BY d.frefsono) as invoice_refs\n                ")
                ->groupBy('h.fstockmtid', 'h.fstockmtno', 'h.fstockmtdate', 'h.fsupplier', 'c.fcustomername', 'h.famount', 'h.fket');
            $this->applyBranchVisibilityScope($query, 'h.fbranchcode');

            $totalRecords = (clone $query)->count();
            if ($search = trim((string) $request->input('search.value', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('h.fstockmtno', 'ilike', "%{$search}%")
                        ->orWhere('c.fcustomername', 'ilike', "%{$search}%")
                        ->orWhere('h.fket', 'ilike', "%{$search}%");
                });
            }
            $filteredRecords = (clone $query)->count();

            $records = $query
                ->orderBy('h.fstockmtdate', 'desc')
                ->orderBy('h.fstockmtno', 'desc')
                ->skip((int) $request->input('start', 0))
                ->take((int) $request->input('length', 10))
                ->get();

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $records->map(fn ($row) => [
                    'fstockmtid' => $row->fstockmtid,
                    'ftagihanno' => trim((string) $row->ftagihanno),
                    'fstockmtdate' => $row->fstockmtdate ? Carbon::parse($row->fstockmtdate)->format('d-m-Y') : '',
                    'invoice_refs' => trim((string) $row->invoice_refs),
                    'fcustomername' => trim((string) $row->fcustomername),
                    'famounttagihan' => (float) $row->famounttagihan,
                    'fnote' => trim((string) $row->fnote),
                    'actions' => view('lembarpenagihan.partials.actions', [
                        'row' => $row,
                        'canEdit' => true,
                        'canDelete' => true,
                    ])->render(),
                ]),
            ]);
        }

        return view('lembarpenagihan.index', compact('canCreate', 'canEdit', 'canDelete', 'showActionsColumn'));
    }

    public function create()
    {
        return view('lembarpenagihan.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $tagihanNo = trim((string) ($data['ftagihanno'] ?? '')) ?: $this->generateTagihanNo(Carbon::parse($data['fstockmtdate']));
        $total = array_sum(array_map('floatval', $data['famount']));
        $userId = substr((string) (auth()->user()->fname ?? auth()->user()->name ?? 'SYSTEM'), 0, 10);

        DB::transaction(function () use ($data, $tagihanNo, $total, $userId) {
            DB::table('trstockmt')->insert([
                'fstockmtid' => $this->nextStockMtId(),
                'fstockmtno' => $tagihanNo,
                'fstockmtcode' => self::CODE,
                'fstockmtdate' => $data['fstockmtdate'],
                'fsupplier' => $data['fcustno'],
                'fket' => $data['fnote'] ?? null,
                'famount' => $total,
                'famountmt' => $total,
                'famountremain' => $total,
                'fbranchcode' => $this->getCurrentBranchCode(),
                'fusercreate' => $userId,
                'fdatetime' => now(),
            ]);
            $this->replaceDetails($tagihanNo, $data, $userId);
        });

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
        $data = $this->validatedData($request);
        $header = $this->headerQuery()->where('h.fstockmtid', $id)->firstOrFail();
        $tagihanNo = trim((string) $header->ftagihanno);
        $total = array_sum(array_map('floatval', $data['famount']));
        $userId = substr((string) (auth()->user()->fname ?? auth()->user()->name ?? 'SYSTEM'), 0, 10);

        DB::transaction(function () use ($data, $id, $tagihanNo, $total, $userId) {
            DB::table('trstockmt')->where('fstockmtid', $id)->update([
                'fstockmtdate' => $data['fstockmtdate'],
                'fsupplier' => $data['fcustno'],
                'fket' => $data['fnote'] ?? null,
                'famount' => $total,
                'famountmt' => $total,
                'famountremain' => $total,
                'fuserupdate' => $userId,
                'fupdatedat' => now(),
            ]);
            DB::table('trtagihandt')->where('ftagihanno', $tagihanNo)->delete();
            $this->replaceDetails($tagihanNo, $data, $userId);
        });

        return redirect()->route('lembarpenagihan.index')->with('success', 'Lembar penagihan berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $header = $this->headerQuery()->where('h.fstockmtid', $id)->firstOrFail();
        DB::transaction(function () use ($header, $id) {
            DB::table('trtagihandt')->where('ftagihanno', $header->ftagihanno)->delete();
            DB::table('trstockmt')->where('fstockmtid', $id)->delete();
        });

        return redirect()->route('lembarpenagihan.index')->with('success', 'Lembar penagihan berhasil dihapus.');
    }

    private function formData(?int $id = null, string $action = 'create'): array
    {
        $header = $id ? $this->headerQuery()->where('h.fstockmtid', $id)->firstOrFail() : null;
        $details = $header ? $this->details($header->ftagihanno) : collect();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);

        return [
            'header' => $header,
            'details' => $details,
            'customers' => $customers,
            'invoices' => $this->availableInvoices($header?->fcustno),
            'returs' => $this->availableReturs($header?->fcustno),
            'action' => $action,
            'nextNo' => $header?->ftagihanno ?? $this->generateTagihanNo(now()),
        ];
    }

    private function headerQuery()
    {
        $query = DB::table('trstockmt as h')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fsupplier')
            ->where('h.fstockmtcode', self::CODE)
            ->selectRaw('h.fstockmtid, h.fstockmtno as ftagihanno, h.fstockmtdate, h.fsupplier as fcustno, c.fcustomername, h.famount as famounttagihan, h.fket as fnote');
        $this->applyBranchVisibilityScope($query, 'h.fbranchcode');

        return $query;
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

    private function availableInvoices(?string $customerCode = null)
    {
        return DB::table('tranmt as i')
            ->where('i.ftrcode', 'INV')
            ->when($customerCode, fn ($q) => $q->where('i.fcustno', $customerCode))
            ->orderBy('i.fsodate', 'desc')
            ->limit(200)
            ->get([
                'i.fsono',
                'i.fsodate',
                'i.fcustno',
                DB::raw('COALESCE(i.famountso, 0) as famountbil'),
                DB::raw('COALESCE(i.fongkosangkut, 0) as fongkos'),
                DB::raw('COALESCE(i.famountremain, i.famountso, 0) as famount'),
            ]);
    }

    private function availableReturs(?string $customerCode = null)
    {
        return DB::table('trstockmt as r')
            ->where('r.fstockmtcode', 'REJ')
            ->when($customerCode, fn ($q) => $q->where('r.fsupplier', $customerCode))
            ->orderBy('r.fstockmtdate', 'desc')
            ->limit(200)
            ->get([
                'r.fstockmtno as fsono',
                'r.fstockmtdate as fsodate',
                'r.fsupplier as fcustno',
                DB::raw('COALESCE(r.famountmt, r.famount, 0) as famountbil'),
                DB::raw('0 as fongkos'),
                DB::raw('COALESCE(r.famountremain, r.famountmt, r.famount, 0) * -1 as famount'),
            ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'ftagihanno' => ['nullable', 'string', 'max:15'],
            'fcustno' => ['required', 'string', 'max:30'],
            'fstockmtdate' => ['required', 'date'],
            'fnote' => ['nullable', 'string', 'max:100'],
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
                'ftrtagihanid' => substr($tagihanNo . '-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT), 0, 20),
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
        $prefix = 'TAG.' . $date->format('ym') . '.';
        $last = DB::table('trstockmt')
            ->where('fstockmtcode', self::CODE)
            ->where('fstockmtno', 'like', $prefix . '%')
            ->orderBy('fstockmtno', 'desc')
            ->value('fstockmtno');
        $next = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function nextStockMtId(): int
    {
        return ((int) DB::table('trstockmt')->max('fstockmtid')) + 1;
    }
}
