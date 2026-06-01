<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestRiskAssessmentFilled extends Notification implements ShouldQueue
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
            'kind'      => 'cr_risk_assessment_filled',
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        return (new MailMessage)
            ->subject('📋 [CR Management System] Risk Assessment Telah Diisi: ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Approver telah mengisi dokumen **Risk Assessment** untuk Change Request Anda.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('Change Request Anda sekarang siap untuk tahap Approval akhir.')
            ->action('Lihat Detail CR', route('cr.show', $cr))
            ->line('Terima kasih.');
    }
}
