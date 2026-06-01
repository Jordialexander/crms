<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestPostMortemFilled extends Notification implements ShouldQueue
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
            'kind'      => 'cr_post_mortem_filled',
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
            'status'    => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = strtoupper($this->changeRequest->status);
        return (new MailMessage)
            ->subject('CR Management System - Post-Mortem Diisi: ' . $this->changeRequest->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Engineer telah mengisi post-mortem untuk CR berikut:')
            ->line('CR: ' . $this->changeRequest->cr_number . ' - ' . $this->changeRequest->title)
            ->line('Status: ' . $statusLabel)
            ->action('Lihat CR & Ambil Keputusan', route('cr.show', $this->changeRequest))
            ->line('Silakan review post-mortem dan ambil keputusan: reschedule atau tutup CR.')
            ->line('Terima kasih.');
    }
}
