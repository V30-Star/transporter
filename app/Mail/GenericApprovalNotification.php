<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericApprovalNotification extends Mailable
{
    use Queueable, SerializesModels;

    public array $fields;

    public array $items;

    public function __construct(
        public string $subjectLine,
        public string $title,
        public string $documentNo,
        public string $approver,
        public ?string $actionUrl = null,
        array $fields = [],
        array $items = []
    ) {
        $this->fields = $fields;
        $this->items = $items;
    }

    public function build()
    {
        return $this->from(config('mail.from.address', 'no-reply@yourapp.local'))
            ->subject($this->subjectLine)
            ->view('emails.generic_approval');
    }
}
