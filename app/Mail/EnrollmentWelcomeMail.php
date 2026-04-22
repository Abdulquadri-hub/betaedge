<?php

namespace App\Mail;

use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnrollmentWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly Batch      $batch,
        public readonly Student    $student,
    ) {}

    public function envelope(): Envelope
    {
        $batchName = $this->batch->batch_name;
        return new Envelope(
            subject: "Welcome to {$batchName} — You're Enrolled! 🎉",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enrollment.welcome',
            with: [
                'studentName' => $this->student->user?->full_name,
                'batchName'   => $this->batch->batch_name,
                'startDate'   => $this->batch->start_date?->format('F j, Y'),
                'endDate'     => $this->batch->end_date?->format('F j, Y'),
                'whatsappLink'=> $this->batch->whatsapp_link,
                'courses'     => $this->batch->batchCourses->map(fn ($bc) => [
                    'title'   => $bc->course?->title,
                    'day'     => $bc->session_day,
                    'time'    => $bc->session_time,
                ])->filter(fn ($c) => $c['title']),
                'loginUrl'    => url('/auth/login'),
            ],
        );
    }
}