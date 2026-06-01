<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestSlaWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly string $warningType, // 'overdue' | 'approaching_end'
        public readonly int $minutesOverdue = 0,
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
            'kind'            => 'cr_sla_warning',
            'cr_id'           => $this->changeRequest->id,
            'cr_number'       => $this->changeRequest->cr_number,
            'title'           => $this->changeRequest->title,
            'status'          => $this->changeRequest->status,
            'warning_type'    => $this->warningType,
            'minutes_overdue' => $this->minutesOverdue,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr       = $this->changeRequest;
        $schedule = $cr->schedule;

        if ($this->warningType === 'overdue') {
            $hours = intdiv($this->minutesOverdue, 60);
            $mins  = $this->minutesOverdue % 60;
            $label = $hours > 0 ? $hours . ' jam ' . $mins . ' menit' : $mins . ' menit';

            $msg = (new MailMessage)
                ->subject('[CR Management System] ⚠️ SLA TERLEWAT — Implementasi Melewati Jadwal: ' . $cr->cr_number)
                ->greeting('Halo ' . $notifiable->name . ',')
                ->line('⚠️ **PERINGATAN:** Implementasi CR berikut telah **melewati jadwal rencana** sebesar ' . $label . '.')
                ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
                ->line('**Engineer (PIC):** ' . ($cr->pic->name ?? '-'))
                ->line('**Layanan Terdampak:** ' . $cr->affected_service);

            if ($schedule) {
                $msg->line('**Jadwal Selesai (Rencana):** ' . $schedule->planned_end->format('d M Y H:i'))
                    ->line('**Status Saat Ini:** In Progress (belum selesai)');
            }

            return $msg
                ->action('Pantau Status CR', route('cr.show', $cr))
                ->line('Segera selesaikan implementasi atau hubungi approver jika ada kendala.')
                ->line('Terima kasih.');
        }

        // approaching_end
        $msg = (new MailMessage)
            ->subject('[CR Management System] ⏰ Implementasi Mendekati Batas Jadwal: ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Implementasi CR berikut **mendekati batas waktu jadwal**.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Engineer (PIC):** ' . ($cr->pic->name ?? '-'))
            ->line('**Layanan Terdampak:** ' . $cr->affected_service);

        if ($schedule) {
            $msg->line('**Jadwal Selesai:** ' . $schedule->planned_end->format('d M Y H:i'));
        }

        return $msg
            ->action('Pantau Status CR', route('cr.show', $cr))
            ->line('Pastikan implementasi selesai sesuai jadwal.')
            ->line('Terima kasih.');
    }
}
