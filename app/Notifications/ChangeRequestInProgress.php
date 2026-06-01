<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestInProgress extends Notification implements ShouldQueue
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
            'kind'      => 'cr_in_progress',
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
            'status'    => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr       = $this->changeRequest;
        $schedule = $cr->schedule;

        $msg = (new MailMessage)
            ->subject('🚀 [CR Management System] Implementasi Dimulai: CR ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Implementasi Change Request berikut telah **dimulai** oleh PIC bersangkutan.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Engineer (PIC):** ' . ($cr->pic->name ?? '-'))
            ->line('**Waktu Mulai Aktual:** ' . now()->format('d M Y, H:i'));

        if ($schedule) {
            $msg->line('**Jadwal Selesai:** ' . $schedule->planned_end->format('d M Y, H:i'));
            if ($schedule->estimated_downtime_minutes > 0) {
                $msg->line('**Estimasi Downtime:** ' . $schedule->estimated_downtime_minutes . ' menit');
            }
        }

        return $msg
            ->action('Pantau Detail CR', route('cr.show', $cr))
            ->line('Anda akan mendapat notifikasi saat implementasi selesai dikerjakan.');
    }
}
