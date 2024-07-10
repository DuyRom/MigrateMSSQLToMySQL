<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MigrationCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public $successfulMigrations;
    public $failedMigrations;

    public function __construct($successfulMigrations, $failedMigrations)
    {
        $this->successfulMigrations = $successfulMigrations;
        $this->failedMigrations = $failedMigrations;
    }

    public function build()
    {
        return $this->view('emails.migration_completed')
                    ->with([
                        'successfulMigrations' => $this->successfulMigrations,
                        'failedMigrations' => $this->failedMigrations,
                    ]);
    }
}
