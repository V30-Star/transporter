<?php

namespace App\Http\Controllers;

use App\Models\Tr_prh;
use App\Models\Tr_prd;
use App\Models\Tr_pod;
use App\Models\Tr_poh;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
  public function showApprovalPage(Request $request, ?string $fprno = null)
  {
    $fprno = $fprno ?? $request->query('fprno');
    if (!$fprno) {
      return redirect()->route('tr_prh.index')->with('error', 'Parameter fprno tidak ada.');
    }

    $hdr = Tr_prh::where('fprno', $fprno)->first();
    if (!$hdr) return redirect()->route('tr_prh.index')->with('error', 'Permintaan Pembelian tidak ditemukan.');
    $dt = Tr_prd::query()
      ->leftJoin('msprd as p', 'p.fprdcode', '=', 'tr_prd.fprdcode')
      ->where('tr_prd.fprnoid', $hdr->fprno)
      ->orderBy('tr_prd.fprdcode')
      ->get([
        'tr_prd.*',
        'p.fprdname as product_name',
        'p.fminstock as stock',
      ]);
    return view('approvalPage', compact('hdr', 'dt'))
      ->with('locked', in_array((int)$hdr->fapproval, [0, 2], true));
  }

  public function approveRequest(Request $request, string $fprno)
  {
    $pr = Tr_prh::where('fprno', $fprno)
      ->where('fapproval_token', $request->token)
      ->first();

    if (!$pr) return back()->with('error', 'Link tidak valid.');
    if (in_array($pr->fapproval, [0, 2])) {
      return redirect()->route('approval.info', $pr->fprno)
        ->with('error', 'PR ini sudah pernah diproses.');
    }

    $pr->fapproval = 2;
    $pr->fuserapproved = 'Guest';
    $pr->fdateapproved = now();
    $pr->save();

    return redirect()->route('approval.info', $pr->fprno);
  }

  public function rejectRequest(Request $request, string $fprno)
  {
    $pr = Tr_prh::where('fprno', $fprno)
      ->where('fapproval_token', $request->token)
      ->first();

    if (!$pr) return back()->with('error', 'Link tidak valid.');
    if (in_array($pr->fapproval, [0, 2])) {
      return redirect()->route('approval.info', $pr->fprno)
        ->with('error', 'PR ini sudah pernah diproses.');
    }

    $pr->fapproval = 0;
    $pr->fapproval_reason = $request->input('note');
    $pr->fuserapproved = 'Guest';
    $pr->fdateapproved = now();
    $pr->save();

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
    if (!$fpono) {
      return redirect()->route('login')->with('error', 'Parameter fpono tidak ada.');
    }

    $hdr = Tr_poh::where('fpono', $fpono)->first();
    if (!$hdr) return redirect()->route('login')->with('error', 'Permintaan Pembelian tidak ditemukan.');
    $dt  = Tr_pod::from('tr_pod as d')
      ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
      ->where('d.fpono', $fpono)
      ->orderBy('d.fprdcode')
      ->get(['d.*', 'p.fprdname as product_name', 'p.fminstock as stock']);

    return view('approvalPagePO', compact('hdr', 'dt'));
  }

  public function approveRequestPO(Request $request, string $fpono)
  {
    $pr = Tr_poh::where('fpono', $fpono)
      ->where('fapproval_token', $request->token)
      ->first();

    if (!$pr) return back()->with('error', 'Link tidak valid.');
    if (in_array($pr->fapproval, [0, 2])) {
      return redirect()->route('approval.po.info', $pr->fpono)
        ->with('error', 'PR ini sudah pernah diproses.');
    }

    $pr->fapproval = 2;
    $pr->fuserapproved = 'Guest';
    $pr->fdateapproved = now();
    $pr->save();

    return redirect()->route('approval.po.info', $pr->fpono);
  }

  public function rejectRequestPO(Request $request, string $fpono)
  {
    $pr = Tr_poh::where('fpono', $fpono)
      ->where('fapproval_token', $request->token)
      ->first();

    if (!$pr) return back()->with('error', 'Link tidak valid.');
    if (in_array($pr->fapproval, [0, 2])) {
      return redirect()->route('approval.po.info', $pr->fpono)
        ->with('error', 'PR ini sudah pernah diproses.');
    }

    $pr->fapproval = 0;
    $pr->fapproval_reason = $request->input('note');
    $pr->fuserapproved = 'Guest';
    $pr->fdateapproved = now();
    $pr->save();

    return redirect()->route('approval.po.info', $pr->fpono);
  }

  public function infoApprovalPagePO(string $fpono)
  {
    $pr = Tr_poh::where('fpono', $fpono)->firstOrFail();

    return view('infoApprovalPagePO', compact('pr'));
  }
}
