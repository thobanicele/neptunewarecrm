<?php

namespace App\Mail;

use App\Models\TenantInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantInvite $invite,
        public string $acceptUrl
    ) {}

    public function build()
    {
        return $this->subject('You have been invited to join ' . $this->invite->tenant->name)
            ->view('emails.tenant_invite');
    }
}
