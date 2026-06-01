<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestNeedsApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly int $step,
        public readonly int $totalSteps,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->notify_email && $notifiable->email) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'cr_needs_approval',
            'cr_id' => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title' => $this->changeRequest->title,
            'status' => $this->changeRequest->status,
            'step' => $this->step,
            'total_steps' => $this->totalSteps,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CR Management System - Approval diperlukan: ' . $this->changeRequest->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Ada Change Request yang membutuhkan persetujuan Anda.')
            ->line('CR: ' . $this->changeRequest->cr_number . ' - ' . $this->changeRequest->title)
            ->line('Tahap approval: ' . $this->step . ' dari ' . $this->totalSteps)
            ->action('Buka detail CR', route('approval.show', $this->changeRequest))
            ->line('Terima kasih.');
    }
}
