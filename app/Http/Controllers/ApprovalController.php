<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tr_pod;
use App\Models\Tr_poh;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\ApprovalState;

class ApprovalController extends Controller
{
    public function showApprovalPage(Request $request, ?string $fprno = null)
    {
        $fprno = $fprno ?? $request->query('fprno');
        $token = trim((string) $request->query('token', ''));

        if (! $fprno || $token === '') {
            return redirect()->route('tr_prh.index')->with('error', 'Link approval tidak valid.');
        }

        $hdr = Tr_prh::query()
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_prh.fbranchcode')
            ->where('tr_prh.fprno', $fprno)
            ->select('tr_prh.*', 'c.fcabangname as cabang_name')
            ->first();
        if (! $hdr) {
            return redirect()->route('tr_prh.index')->with('error', 'Link approval tidak valid atau data tidak ditemukan.');
        }

        $stage = $this->resolveStageByToken($hdr, $token);
        if ($stage === null) {
            return redirect()->route('tr_prh.index')->with('error', 'Link approval tidak valid.');
        }

        $dt = Tr_prd::query()
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'tr_prd.fprdcode')
            ->where('tr_prd.fprno', $hdr->fprno)
            ->orderBy('tr_prd.fprdcode')
            ->get([
                'tr_prd.*',
                'p.fprdname as product_name',
                'p.fminstock as stock',
            ]);

        return view('approvalPage', compact('hdr', 'dt'))
            ->with('locked', $this->isApprovalProcessed($hdr, $stage));
    }

    public function approveRequest(Request $request, string $fprno)
    {
        $pr = Tr_prh::where('fprno', $fprno)->first();
        if (! $pr) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($pr, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($pr, $stage)) {
            return redirect()->route('approval.info', $pr->fprno)->with('error', 'PR ini sudah pernah diproses sebelumnya.');
        }

        $this->markApproved($pr, $stage);

        return redirect()->route('approval.info', $pr->fprno);
    }

    public function rejectRequest(Request $request, string $fprno)
    {
        $pr = Tr_prh::where('fprno', $fprno)->first();
        if (! $pr) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($pr, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($pr, $stage)) {
            return redirect()->route('approval.info', $pr->fprno)->with('error', 'PR ini sudah pernah diproses sebelumnya.');
        }

        $this->markRejected($pr, $stage, (string) $request->input('note', ''));

        return redirect()->route('approval.info', $pr->fprno);
    }

    public function infoApprovalPage(string $fprno)
    {
        $pr = Tr_prh::where('fprno', $fprno)->firstOrFail();

        return view('infoApprovalPage', compact('pr'));
    }

    public function showApprovalPagePO(Request $request, ?string $fpono = null)
    {
        $fpono = $fpono ?? $request->query('fpono');
        $token = trim((string) $request->query('token', ''));

        if (! $fpono || $token === '') {
            return redirect()->route('login')->with('error', 'Link approval tidak valid.');
        }

        $hdr = Tr_poh::where('fpono', $fpono)->first();
        if (! $hdr) {
            return redirect()->route('login')->with('error', 'Link approval tidak valid atau data tidak ditemukan.');
        }

        $stage = $this->resolveStageByToken($hdr, $token);
        if ($stage === null) {
            return redirect()->route('login')->with('error', 'Link approval tidak valid.');
        }

        $dt = Tr_pod::from('tr_pod as d')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
            ->where(function ($q) use ($fpono) {
                $q->where('d.fpono', $fpono)
                    ->orWhere(function ($inner) use ($fpono) {
                        $inner->whereNull('d.fpono')
                            ->where('d.frefdtno', $fpono);
                    })
                    ->orWhere(function ($inner) use ($fpono) {
                        $inner->where('d.fpono', '')
                            ->where('d.frefdtno', $fpono);
                    });
            })
            ->orderBy('d.fprdcode')
            ->get(['d.*', 'p.fprdname as product_name', 'p.fminstock as stock']);

        return view('approvalPagePO', compact('hdr', 'dt'))
            ->with('locked', $this->isApprovalProcessed($hdr, $stage));
    }

    public function approveRequestPO(Request $request, string $fpono)
    {
        $po = Tr_poh::where('fpono', $fpono)->first();
        if (! $po) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($po, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($po, $stage)) {
            return redirect()->route('approval.po.info', $po->fpono)->with('error', 'PO ini sudah pernah diproses sebelumnya.');
        }

        $this->markApproved($po, $stage);

        return redirect()->route('approval.po.info', $po->fpono);
    }

    public function rejectRequestPO(Request $request, string $fpono)
    {
        $po = Tr_poh::where('fpono', $fpono)->first();
        if (! $po) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($po, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($po, $stage)) {
            return redirect()->route('approval.po.info', $po->fpono)->with('error', 'PO ini sudah pernah diproses sebelumnya.');
        }

        $this->markRejected($po, $stage, (string) $request->input('note', ''));

        return redirect()->route('approval.po.info', $po->fpono);
    }

    public function infoApprovalPagePO(string $fpono)
    {
        $pr = Tr_poh::where('fpono', $fpono)->firstOrFail();

        return view('infoApprovalPagePO', compact('pr'));
    }

    public function showProductApprovalPage(Request $request, int $fprdid)
    {
        $token = trim((string) $request->query('token', ''));
        $product = Product::findOrFail($fprdid);
        $stage = $this->resolveStageByToken($product, $token);

        if ($token === '' || $stage === null) {
            return redirect()->route('product.index')->with('error', 'Link approval tidak valid.');
        }

        return view('approvalGenericPage', [
            'record' => $product,
            'title' => 'Approval Produk',
            'documentNo' => $product->fprdcode,
            'detailRows' => [],
            'fields' => [
                ['label' => 'Kode Produk', 'value' => $product->fprdcode ?? '-'],
                ['label' => 'Nama Produk', 'value' => $product->fprdname ?? '-'],
                ['label' => 'Satuan', 'value' => $product->fsatuankecil ?? '-'],
                ['label' => 'Min. Stok', 'value' => number_format((float) ($product->fminstock ?? 0), 2, ',', '.')],
            ],
            'approveRoute' => route('approval.product.submit', $product->fprdid),
            'rejectRoute' => route('approval.product.reject', $product->fprdid),
            'token' => $token,
            'locked' => $this->isApprovalProcessed($product, $stage),
            'approvedMessage' => 'Produk ini sudah disetujui.',
            'rejectedMessage' => 'Produk ini sudah ditolak.',
        ]);
    }

    public function approveProduct(Request $request, int $fprdid)
    {
        $product = Product::findOrFail($fprdid);
        $stage = $this->resolveStageByToken($product, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($product, $stage)) {
            return redirect()->route('approval.product.info', $product->fprdid)->with('error', 'Produk ini sudah pernah diproses sebelumnya.');
        }

        $this->markApproved($product, $stage);

        return redirect()->route('approval.product.info', $product->fprdid);
    }

    public function rejectProduct(Request $request, int $fprdid)
    {
        $product = Product::findOrFail($fprdid);
        $stage = $this->resolveStageByToken($product, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($product, $stage)) {
            return redirect()->route('approval.product.info', $product->fprdid)->with('error', 'Produk ini sudah pernah diproses sebelumnya.');
        }

        $this->markRejected($product, $stage, (string) $request->input('note', ''));

        return redirect()->route('approval.product.info', $product->fprdid);
    }

    public function productInfo(int $fprdid)
    {
        $product = Product::findOrFail($fprdid);

        return view('infoApprovalGenericPage', [
            'record' => $product,
            'documentNo' => $product->fprdcode,
            'title' => 'Status Approval Produk',
        ]);
    }

    public function showSalesOrderApprovalPage(Request $request, string $fsono)
    {
        $token = trim((string) $request->query('token', ''));
        $header = DB::table('trsomt')->where('fsono', $fsono)->first();
        if (! $header) {
            return redirect()->route('salesorder.index')->with('error', 'Link approval tidak valid atau data tidak ditemukan.');
        }

        $stage = $this->resolveStageByToken($header, $token);
        if ($token === '' || $stage === null) {
            return redirect()->route('salesorder.index')->with('error', 'Link approval tidak valid.');
        }

        $items = DB::table('trsodt as d')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->where('d.fsono', $fsono)
            ->orderBy('d.fnou')
            ->get([
                'd.fprdcode as code',
                DB::raw("COALESCE(p.fprdname, '-') as name"),
                'd.fqty as qty',
                'd.fsatuan',
                'd.fprice as price',
                'd.famount as total',
            ]);

        return view('approvalGenericPage', [
            'record' => $header,
            'title' => 'Approval Sales Order',
            'documentNo' => $header->fsono,
            'detailRows' => $items,
            'fields' => [
                ['label' => 'Tanggal', 'value' => $header->fsodate ? date('d-m-Y', strtotime((string) $header->fsodate)) : '-'],
                ['label' => 'Customer', 'value' => $header->fcustno ?? '-'],
                ['label' => 'Total', 'value' => format_number($header->famountso ?? 0)],
            ],
            'approveRoute' => route('approval.salesorder.submit', $header->fsono),
            'rejectRoute' => route('approval.salesorder.reject', $header->fsono),
            'token' => $token,
            'locked' => $this->isApprovalProcessed($header, $stage),
            'approvedMessage' => 'Sales Order ini sudah disetujui.',
            'rejectedMessage' => 'Sales Order ini sudah ditolak.',
        ]);
    }

    public function approveSalesOrder(Request $request, string $fsono)
    {
        $header = DB::table('trsomt')->where('fsono', $fsono)->first();
        if (! $header) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($header, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($header, $stage)) {
            return redirect()->route('approval.salesorder.info', $fsono)->with('error', 'Sales Order ini sudah pernah diproses sebelumnya.');
        }

        $this->markApprovedTable('trsomt', 'fsono', $fsono, $stage);

        return redirect()->route('approval.salesorder.info', $fsono);
    }

    public function rejectSalesOrder(Request $request, string $fsono)
    {
        $header = DB::table('trsomt')->where('fsono', $fsono)->first();
        if (! $header) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($header, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($header, $stage)) {
            return redirect()->route('approval.salesorder.info', $fsono)->with('error', 'Sales Order ini sudah pernah diproses sebelumnya.');
        }

        $this->markRejectedTable('trsomt', 'fsono', $fsono, $stage, (string) $request->input('note', ''));

        return redirect()->route('approval.salesorder.info', $fsono);
    }

    public function salesOrderInfo(string $fsono)
    {
        $record = DB::table('trsomt')->where('fsono', $fsono)->first();
        abort_if(! $record, 404);

        return view('infoApprovalGenericPage', [
            'record' => $record,
            'documentNo' => $fsono,
            'title' => 'Status Approval Sales Order',
        ]);
    }

    public function showInvoiceApprovalPage(Request $request, string $fsono)
    {
        $token = trim((string) $request->query('token', ''));
        $header = DB::table('tranmt')->where('fsono', $fsono)->first();
        if (! $header) {
            return redirect()->route('invoice.index')->with('error', 'Link approval tidak valid atau data tidak ditemukan.');
        }

        $stage = $this->resolveStageByToken($header, $token);
        if ($token === '' || $stage === null) {
            return redirect()->route('invoice.index')->with('error', 'Link approval tidak valid.');
        }

        $items = DB::table('trandt as d')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->where('d.fsono', $fsono)
            ->orderBy('d.fnou')
            ->get([
                'd.fprdcode as code',
                DB::raw("COALESCE(p.fprdname, '-') as name"),
                'd.fqty as qty',
                'd.fsatuan',
                'd.fprice as price',
                'd.famount as total',
            ]);

        return view('approvalGenericPage', [
            'record' => $header,
            'title' => 'Approval Faktur Penjualan',
            'documentNo' => $header->fsono,
            'detailRows' => $items,
            'fields' => [
                ['label' => 'Tanggal', 'value' => $header->fsodate ? date('d-m-Y', strtotime((string) $header->fsodate)) : '-'],
                ['label' => 'Customer', 'value' => $header->fcustno ?? '-'],
                ['label' => 'Total', 'value' => format_number($header->famountso ?? 0)],
            ],
            'approveRoute' => route('approval.invoice.submit', $header->fsono),
            'rejectRoute' => route('approval.invoice.reject', $header->fsono),
            'token' => $token,
            'locked' => $this->isApprovalProcessed($header, $stage),
            'approvedMessage' => 'Faktur Penjualan ini sudah disetujui.',
            'rejectedMessage' => 'Faktur Penjualan ini sudah ditolak.',
        ]);
    }

    public function approveInvoice(Request $request, string $fsono)
    {
        $header = DB::table('tranmt')->where('fsono', $fsono)->first();
        if (! $header) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($header, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($header, $stage)) {
            return redirect()->route('approval.invoice.info', $fsono)->with('error', 'Faktur Penjualan ini sudah pernah diproses sebelumnya.');
        }

        $this->markApprovedTable('tranmt', 'fsono', $fsono, $stage);

        return redirect()->route('approval.invoice.info', $fsono);
    }

    public function rejectInvoice(Request $request, string $fsono)
    {
        $header = DB::table('tranmt')->where('fsono', $fsono)->first();
        if (! $header) {
            return back()->with('error', 'Link approval tidak valid.');
        }

        $stage = $this->resolveStageByToken($header, trim((string) $request->input('token')));
        if ($stage === null) {
            return back()->with('error', 'Link approval tidak valid.');
        }
        if ($this->isApprovalProcessed($header, $stage)) {
            return redirect()->route('approval.invoice.info', $fsono)->with('error', 'Faktur Penjualan ini sudah pernah diproses sebelumnya.');
        }

        $this->markRejectedTable('tranmt', 'fsono', $fsono, $stage, (string) $request->input('note', ''));

        return redirect()->route('approval.invoice.info', $fsono);
    }

    public function invoiceInfo(string $fsono)
    {
        $record = DB::table('tranmt')->where('fsono', $fsono)->first();
        abort_if(! $record, 404);

        return view('infoApprovalGenericPage', [
            'record' => $record,
            'documentNo' => $fsono,
            'title' => 'Status Approval Faktur Penjualan',
        ]);
    }

    private function resolveStageByToken($record, string $token): ?int
    {
        if ($token === '') {
            return null;
        }

        if (trim((string) data_get($record, 'fapproval_token')) === $token) {
            return 1;
        }

        if (trim((string) data_get($record, 'fapproval_token2')) === $token) {
            return 2;
        }

        return null;
    }

    private function isApprovalProcessed($record, int $stage): bool
    {
        if (ApprovalState::isApprovedRecord($record)) {
            return true;
        }

        $statusField = $stage === 2 ? 'fapproval2' : 'fapproval';

        return ApprovalState::isRejectedValue(data_get($record, $statusField));
    }

    private function markApproved($model, int $stage): void
    {
        $statusField = $stage === 2 ? 'fapproval2' : 'fapproval';
        $userField = $stage === 2 ? 'fuserapproved2' : 'fuserapproved';
        $dateField = $stage === 2 ? 'fdateapproved2' : 'fdateapproved';

        $model->{$statusField} = '2';
        $model->{$userField} = 'Guest';
        $model->{$dateField} = now();
        $model->save();
    }

    private function markRejected($model, int $stage, string $note): void
    {
        $statusField = $stage === 2 ? 'fapproval2' : 'fapproval';
        $userField = $stage === 2 ? 'fuserapproved2' : 'fuserapproved';
        $dateField = $stage === 2 ? 'fdateapproved2' : 'fdateapproved';
        $reasonField = $stage === 2 ? 'fapproval_reason2' : 'fapproval_reason';

        $model->{$statusField} = '0';
        $model->{$reasonField} = $note;
        $model->{$userField} = 'Guest';
        $model->{$dateField} = now();
        $model->save();
    }

    private function markApprovedTable(string $table, string $key, string $value, int $stage): void
    {
        DB::table($table)
            ->where($key, $value)
            ->update([
                $stage === 2 ? 'fapproval2' : 'fapproval' => '2',
                $stage === 2 ? 'fuserapproved2' : 'fuserapproved' => 'Guest',
                $stage === 2 ? 'fdateapproved2' : 'fdateapproved' => now(),
            ]);
    }

    private function markRejectedTable(string $table, string $key, string $value, int $stage, string $note): void
    {
        DB::table($table)
            ->where($key, $value)
            ->update([
                $stage === 2 ? 'fapproval2' : 'fapproval' => '0',
                $stage === 2 ? 'fuserapproved2' : 'fuserapproved' => 'Guest',
                $stage === 2 ? 'fdateapproved2' : 'fdateapproved' => now(),
                $stage === 2 ? 'fapproval_reason2' : 'fapproval_reason' => $note,
            ]);
    }
}
