<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly string $reminderType, // '3_days', '1_day', 'few_hours'
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
            'kind'          => 'cr_reminder',
            'cr_id'         => $this->changeRequest->id,
            'cr_number'     => $this->changeRequest->cr_number,
            'title'         => $this->changeRequest->title,
            'status'        => $this->changeRequest->status,
            'reminder_type' => $this->reminderType,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr       = $this->changeRequest;
        $schedule = $cr->schedule;

        $timeLabel = match($this->reminderType) {
            '3_days'    => '3 hari lagi',
            '1_day'     => 'besok',
            'few_hours' => 'beberapa jam lagi',
            default     => 'segera',
        };

        $urgency = match($this->reminderType) {
            'few_hours' => '🔴 SEGERA',
            '1_day'     => '🟡 BESOK',
            default     => '🔵 REMINDER',
        };

        $msg = (new MailMessage)
            ->subject('[CR Management System] ' . $urgency . ' Jadwal Implementasi ' . $timeLabel . ': ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Pengingat: jadwal implementasi CR berikut akan tiba **' . $timeLabel . '**.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Engineer (PIC):** ' . ($cr->pic->name ?? '-'))
            ->line('**Layanan Terdampak:** ' . $cr->affected_service)
            ->line('**Prioritas:** ' . strtoupper($cr->priority));

        if ($schedule) {
            $msg->line('**Jadwal Mulai:** ' . $schedule->planned_start->format('d M Y H:i'))
                ->line('**Jadwal Selesai:** ' . $schedule->planned_end->format('d M Y H:i'))
                ->line('**Estimasi Downtime:** ' . $schedule->estimated_downtime_minutes . ' menit');

            if ($schedule->notes) {
                $msg->line('**Catatan Jadwal:** ' . $schedule->notes);
            }
        }

        return $msg
            ->action('Lihat Detail CR', route('cr.show', $cr))
            ->line('Pastikan semua persiapan sudah dilakukan sebelum waktu implementasi.')
            ->line('Terima kasih.');
    }
}
