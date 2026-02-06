<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CompanyStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Company $company,
        public ?Carbon $from,
        public ?Carbon $to,
        public string $pdfBinary
    ) {}

    public function build()
    {
        $subject = "Statement - {$this->company->name} - {$this->tenant->name}";

        return $this->subject($subject)
            ->view('emails.company_statement')
            ->attachData(
                $this->pdfBinary,
                "Statement-{$this->company->id}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}

