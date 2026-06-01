<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestClosed extends Notification implements ShouldQueue
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
            'kind'          => 'cr_closed',
            'cr_id'         => $this->changeRequest->id,
            'cr_number'     => $this->changeRequest->cr_number,
            'title'         => $this->changeRequest->title,
            'status'        => $this->changeRequest->status,
            'closed_reason' => $this->changeRequest->closed_reason,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;

        [$subject, $headline, $detail] = match($cr->closed_reason) {
            'completed' => [
                '🔒 [CR Management System] CR Ditutup (Selesai): ' . $cr->cr_number,
                'Siklus Change Request telah **selesai dan ditutup secara permanen** karena implementasi berhasil.',
                'Seluruh proses implementasi berjalan sesuai rencana.',
            ],
            'rejected' => [
                '🔒 [CR Management System] CR Ditutup (Ditolak): ' . $cr->cr_number,
                'Change Request ini telah **ditutup secara permanen** setelah sebelumnya ditolak dan tidak dilanjutkan.',
                'CR tidak dapat diproses lebih lanjut.',
            ],
            'canceled' => [
                '🔒 [CR Management System] CR Ditutup (Dibatalkan): ' . $cr->cr_number,
                'Change Request ini telah **ditutup karena dibatalkan**.',
                $cr->cancellation_note ? 'Alasan Batal: ' . $cr->cancellation_note : '',
            ],
            'failed' => [
                '🔒 [CR Management System] CR Ditutup (Gagal): ' . $cr->cr_number,
                'Change Request ini telah **ditutup secara permanen** akibat kegagalan implementasi tanpa opsi penjadwalan ulang.',
                $cr->post_mortem_note ? 'Catatan Kegagalan: ' . $cr->post_mortem_note : 'Silakan cek detail Log untuk informasi lanjut.',
            ],
            default => [
                '🔒 [CR Management System] CR Ditutup: ' . $cr->cr_number,
                'Change Request ini telah **ditutup secara permanen**.',
                '',
            ],
        };

        $msg = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line($headline)
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Engineer (PIC):** ' . ($cr->pic->name ?? '-'));

        if ($detail) {
            $msg->line($detail);
        }

        if ($cr->closed_at) {
            $msg->line('**Ditutup pada:** ' . $cr->closed_at->format('d M Y, H:i'));
        }

        return $msg
            ->action('Lihat Arsip CR', route('cr.show', $cr))
            ->line('Terima kasih atas partisipasi Anda dalam siklus CR ini.');
    }
}
