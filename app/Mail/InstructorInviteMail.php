<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstructorInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant  $tenant,
        public readonly User    $instructor,
        public readonly bool    $isNewAccount,
        public readonly ?string $tempPassword = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to teach at {$this->tenant->name} on BetaEdge",
        );
    }

    public function content(): Content
    {
        $domain  = config('app.main_domain');
        $loginUrl = "https://{$domain}/auth/login";

        return new Content(
            markdown: 'emails.instructor.invite',
            with: [
                'instructorName' => $this->instructor->full_name,
                'schoolName'     => $this->tenant->name,
                'isNewAccount'   => $this->isNewAccount,
                'tempPassword'   => $this->tempPassword,
                'loginUrl'       => $loginUrl,
                'email'          => $this->instructor->email,
            ],
        );
    }
}