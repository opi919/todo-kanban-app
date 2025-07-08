<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TodoDueSoonNotification extends Notification
{
    public function __construct(public $todo) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reminder: Task Due Tomorrow')
            ->line("Task: {$this->todo->title}")
            ->line("Due: {$this->todo->due_date->format('d M Y')}")
            ->action('View Task', url("/admin/todos/{$this->todo->id}/edit"))
            ->line('Please complete your task on time.');
    }
}
