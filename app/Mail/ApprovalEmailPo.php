<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApprovalEmailPo extends Mailable
{
  use Queueable, SerializesModels;

  public $hdr;
  public $dt;
  public $productName;
  public $approver;
  public $subjectMail;

  public function __construct($hdr, $dt, $productName, $approver, $subjectMail = 'Order Pembelian (PO)')
  {
    $this->hdr         = $hdr;
    $this->dt          = $dt;
    $this->productName = $productName;
    $this->approver    = $approver;
    $this->subjectMail = $subjectMail;
  }

  public function build()
  {
    return $this->from(config('mail.from.address', 'no-reply@yourapp.local'))
      ->subject($this->subjectMail)
      ->view('emails.approvalpo')
      ->with([
        'hdr'         => $this->hdr,
        'dt'          => $this->dt,
        'productName' => $this->productName,
        'approver'    => $this->approver,
      ]);
  }
}
