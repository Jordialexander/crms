<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestUpcomingReminder extends Notification implements ShouldQueue
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
            'kind'      => 'cr_upcoming_reminder',
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
            ->subject('⏰ [CR Management System] PENGINGAT: Implementasi Mendekati Jadwal ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Mengingatkan bahwa jadwal mulai implementasi Change Request Anda sudah semakin dekat (dalam 24 jam ke depan).')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Jadwal Mulai:** ' . ($sched ? $sched->planned_start->format('d M Y, H:i') : '-'))
            ->line('Mohon kepada PIC untuk bersiap memulai implementasi tepat waktu.')
            ->action('Lihat Detail CR', route('cr.show', $cr));
    }
}
