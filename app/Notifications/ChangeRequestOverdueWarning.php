<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestOverdueWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ChangeRequest $changeRequest) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->notify_email && $notifiable->email) $channels[] = 'mail';
        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind'      => 'cr_overdue_warning',
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        $sched = $cr->schedule;
        return (new MailMessage)
            ->subject('⚠️ [CR Management System] PERINGATAN: CR Melewati Batas Waktu ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Implementasi Change Request berikut telah **melewati batas waktu selesai** yang dijadwalkan, namun sistem belum mencatat log penyelesaian.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Batas Waktu Selesai:** ' . ($sched ? $sched->planned_end->format('d M Y, H:i') : '-'))
            ->line('Jika implementasi telah selesai, mohon PIC untuk segera mensubmit hasil implementasi di sistem.')
            ->action('Submit Hasil Implementasi', route('cr.show', $cr));
    }
}
