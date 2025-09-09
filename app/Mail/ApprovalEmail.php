<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApprovalEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $hdr;
    public $dt;
    public $productName;
    public $approver;

    // Constructor accepts hdr, dt, productName, and approver
    public function __construct($hdr, $dt, $productName, $approver)
    {
        $this->hdr = $hdr;
        $this->dt = $dt;
        $this->productName = $productName;
        $this->approver = $approver;
    }

    // Build the email
    public function build()
    {
        return $this->from('kualijawa30@gmail.com')
            ->subject('Permintaan Pembelian')
            ->view('emails.approval') // Ensure the view is correctly defined
            ->with([
                'hdr' => $this->hdr,  // Pass hdr to the email
                'dt' => $this->dt,    // Pass dt to the email
                'productName' => $this->productName,
                'approver' => $this->approver,
            ]);
    }
}
