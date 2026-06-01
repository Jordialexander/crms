<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestNeedReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly User $approver,
        public readonly int $step = 1,
        public readonly int $totalSteps = 1,
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
            'kind'        => 'cr_need_review',
            'cr_id'       => $this->changeRequest->id,
            'cr_number'   => $this->changeRequest->cr_number,
            'title'       => $this->changeRequest->title,
            'step'        => $this->step,
            'total_steps' => $this->totalSteps,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        return (new MailMessage)
            ->subject('🔔 [CR Management System] Aksi Diperlukan: Review CR ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Anda menerima permintaan persetujuan untuk Change Request berikut yang saat ini menunggu **review awal** dari Anda.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Requester:** ' . ($cr->requester->name ?? '-'))
            ->line('**Prioritas:** ' . strtoupper($cr->priority))
            ->action('Mulai Review', route('cr.show', $cr))
            ->line('Mohon segera periksa kelengkapan datanya dan mulai proses review. Terima kasih.');
    }
}
