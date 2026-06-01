<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestAssignedToEngineer extends Notification implements ShouldQueue
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
            'kind'      => 'cr_assigned_engineer',
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
            ->subject('👨‍💻 [CR Management System] Tugas Baru: Penjadwalan CR ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Sebuah Change Request telah **disetujui** dan masuk ke antrean tugas Anda (atau tim Engineer).')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Prioritas:** ' . strtoupper($cr->priority))
            ->line('**Layanan Terdampak:** ' . $cr->affected_service)
            ->line('Mohon untuk segera menentukan dan menginput **jadwal implementasi** pada CR tersebut melalui sistem.')
            ->action('Atur Jadwal CR', route('cr.show', $cr))
            ->line('Terima kasih atas kerja samanya.');
    }
}
