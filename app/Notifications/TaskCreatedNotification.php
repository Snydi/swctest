<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Новая задача: ' . $this->task->header)
            ->line('Вам назначена новая задача.')
            ->line('Заголовок: ' . $this->task->header)
            ->line('Описание: ' . $this->task->description)
            ->line('Статус: ' . $this->getStatusText($this->task->status))
            ->action('Просмотреть задачу', url('/tasks/' . $this->task->id))
            ->line('Спасибо за использование нашего приложения!');
    }

    private function getStatusText(string $status): string
    {
        return match ($status) {
            'planned' => 'Запланирована',
            'in_progress' => 'В работе',
            'done' => 'Выполнена',
            default => $status,
        };
    }
}
