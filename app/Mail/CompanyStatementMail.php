<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\CarbonInterface;

class CompanyStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Company $company,
        public ?CarbonInterface $dateFrom,
        public ?CarbonInterface $dateTo,
        public string $pdfBinary
    ) {}

    public function build()
    {
        $subject = "Statement - {$this->company->name} - {$this->tenant->name}";

        return $this->subject($subject)
            ->view('tenant.emails.company_statement', [
                'tenant'   => $this->tenant,
                'company'  => $this->company,
                'dateFrom' => $this->dateFrom,
                'dateTo'   => $this->dateTo,
            ])
            ->attachData(
                $this->pdfBinary,
                "Statement-{$this->company->id}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}


