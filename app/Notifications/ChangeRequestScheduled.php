<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly bool $isUpdate = false,
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
            'kind'      => 'cr_scheduled',
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
            'status'    => $this->changeRequest->status,
            'is_update' => $this->isUpdate,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr       = $this->changeRequest;
        $schedule = $cr->schedule;

        $subject  = $this->isUpdate
            ? '🔄 [CR Management System] Jadwal Diperbarui: CR ' . $cr->cr_number
            : '📅 [CR Management System] Jadwal Ditetapkan: CR ' . $cr->cr_number;

        $headline = $this->isUpdate
            ? 'Jadwal implementasi untuk Change Request berikut telah **diperbarui**.'
            : 'Jadwal implementasi untuk Change Request berikut telah **ditetapkan** dan segera diproses.';

        $msg = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line($headline)
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Engineer (PIC):** ' . ($cr->pic->name ?? 'Belum ditugaskan'));

        if ($schedule) {
            $msg->line('**Jadwal Mulai:** ' . $schedule->planned_start->format('d M Y, H:i'))
                ->line('**Jadwal Selesai:** ' . $schedule->planned_end->format('d M Y, H:i'))
                ->line('**Estimasi Downtime:** ' . $schedule->estimated_downtime_minutes . ' menit');

            if ($schedule->notes) {
                $msg->line('**Catatan:** ' . $schedule->notes);
            }
        }

        return $msg
            ->action('Lihat Detail Jadwal', route('cr.show', $cr))
            ->line('Terima kasih.');
    }
}
