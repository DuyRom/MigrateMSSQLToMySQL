<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MigrationErrorNotification extends Notification
{
    use Queueable;

    protected $tableName;
    protected $errorMessage;
    protected $migrationType;

    public function __construct($tableName, $errorMessage, $migrationType)
    {
        $this->tableName = $tableName;
        $this->errorMessage = $errorMessage;
        $this->migrationType = $migrationType;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Migration Error Notification - {$this->migrationType}")
            ->line("An error occurred while migrating {$this->migrationType} for table: {$this->tableName}")
            ->line("Error Message: {$this->errorMessage}")
            ->line('Please check the logs for more details.');
    }

    public function toArray($notifiable)
    {
        return [
            'table_name' => $this->tableName,
            'error_message' => $this->errorMessage,
            'migration_type' => $this->migrationType,
        ];
    }
}