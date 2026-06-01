<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestRescheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ChangeRequest $changeRequest) {}

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
            'kind'      => 'cr_rescheduled',
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
            'status'    => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        return (new MailMessage)
            ->subject('🔄 [CR Management System] Izin Reschedule Diberikan: ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Approver telah **mengizinkan** penjadwalan ulang (Reschedule) untuk Change Request berikut:')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('Silakan kepada PIC untuk segera menetapkan jadwal implementasi yang baru melalui sistem.')
            ->action('Atur Ulang Jadwal', route('cr.show', $cr))
            ->line('Terima kasih atas kerja samanya.');
    }
}
