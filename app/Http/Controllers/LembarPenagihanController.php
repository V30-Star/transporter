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
            $query = DB::table('trtagihanmt as h')
                ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fcustno')
                ->leftJoin('trtagihandt as d', 'd.ftagihanno', '=', 'h.ftagihanno')
                ->selectRaw("\n                    h.ftagihanid,\n                    h.ftagihanno,\n                    h.ftagihandate,\n                    h.fcustno,\n                    c.fcustomername,\n                    h.famounttagihan,\n                    h.fnote,\n                    STRING_AGG(TRIM(d.frefsono), ', ' ORDER BY d.frefsono) as invoice_refs\n                ")
                ->groupBy('h.ftagihanid', 'h.ftagihanno', 'h.ftagihandate', 'h.fcustno', 'c.fcustomername', 'h.famounttagihan', 'h.fnote');

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

        return view('lembarpenagihan.index', compact('canCreate', 'canEdit', 'canDelete', 'showActionsColumn'));
    }

    public function create()
    {
        return view('lembarpenagihan.create', $this->formData());
    }

    public function pickableInvoices(Request $request)
    {
        $customerCode = trim((string) $request->input('fcustno', $request->input('customer_code', '')));
        $search = trim((string) $request->input('search', ''));

        $query = DB::table('tranmt as i')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'i.fcustno')
            ->where('i.ftrcode', 'INV')
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

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $tagihanNo = trim((string) ($data['ftagihanno'] ?? '')) ?: $this->generateTagihanNo(Carbon::parse($data['ftagihandate']));
        $total = array_sum(array_map('floatval', $data['famount']));
        $userId = substr((string) (auth()->user()->fname ?? auth()->user()->name ?? 'SYSTEM'), 0, 10);

        DB::transaction(function () use ($data, $tagihanNo, $total, $userId) {
            DB::table('trtagihanmt')->insert([
                'ftagihanno' => $tagihanNo,
                'ftagihandate' => $data['ftagihandate'],
                'fcustno' => $data['fcustno'],
                'ftrancode' => self::CODE,
                'fnote' => $data['fnote'] ?? null,
                'famounttagihan' => $total,
                'fuserid' => $userId,
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
        $data = $this->validatedData($request, $id);
        $header = $this->headerQuery()->where('h.ftagihanid', $id)->firstOrFail();
        $tagihanNo = trim((string) $header->ftagihanno);
        $total = array_sum(array_map('floatval', $data['famount']));
        $userId = substr((string) (auth()->user()->fname ?? auth()->user()->name ?? 'SYSTEM'), 0, 10);

        DB::transaction(function () use ($data, $id, $tagihanNo, $total, $userId) {
            DB::table('trtagihanmt')->where('ftagihanid', $id)->update([
                'ftagihandate' => $data['ftagihandate'],
                'fcustno' => $data['fcustno'],
                'ftrancode' => self::CODE,
                'fnote' => $data['fnote'] ?? null,
                'famounttagihan' => $total,
                'fuserid' => $userId,
                'fdatetime' => now(),
            ]);
            DB::table('trtagihandt')->where('ftagihanno', $tagihanNo)->delete();
            $this->replaceDetails($tagihanNo, $data, $userId);
        });

        return redirect()->route('lembarpenagihan.index')->with('success', 'Lembar penagihan berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $header = $this->headerQuery()->where('h.ftagihanid', $id)->firstOrFail();
        DB::transaction(function () use ($header, $id) {
            DB::table('trtagihandt')->where('ftagihanno', $header->ftagihanno)->delete();
            DB::table('trtagihanmt')->where('ftagihanid', $id)->delete();
        });

        return redirect()->route('lembarpenagihan.index')->with('success', 'Lembar penagihan berhasil dihapus.');
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
            ->selectRaw('h.ftagihanid, h.ftagihanno, h.ftagihandate, h.fcustno, c.fcustomername, h.famounttagihan, h.fnote');
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
                'max:15',
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
        $last = DB::table('trtagihanmt')
            ->where('ftagihanno', 'like', $prefix . '%')
            ->orderBy('ftagihanno', 'desc')
            ->value('ftagihanno');
        $next = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
