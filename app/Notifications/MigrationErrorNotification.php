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

    public function __construct($tableName, $errorMessage)
    {
        $this->tableName = $tableName;
        $this->errorMessage = $errorMessage;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Migration Error Notification')
                    ->line("An error occurred while migrating the table: {$this->tableName}")
                    ->line("Error Message: {$this->errorMessage}")
                    ->line('Please check the logs for more details.');
    }

    public function toArray($notifiable)
    {
        return [
            'table_name' => $this->tableName,
            'error_message' => $this->errorMessage,
        ];
    }
}
