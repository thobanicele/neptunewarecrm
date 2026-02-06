<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Invoice $invoice,
        public string $pdfBinary
    ) {}

    public function build()
    {
        $subject = "Invoice {$this->invoice->invoice_number} - {$this->tenant->name}";

        return $this->subject($subject)
            ->view('emails.invoice')
            ->attachData(
                $this->pdfBinary,
                "Invoice-{$this->invoice->invoice_number}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}


