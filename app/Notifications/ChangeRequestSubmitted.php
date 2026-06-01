<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly ?User $currentApprover = null,
        public readonly int $step = 1,
        public readonly int $totalSteps = 1,
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
            'kind'             => 'cr_submitted',
            'cr_id'            => $this->changeRequest->id,
            'cr_number'        => $this->changeRequest->cr_number,
            'title'            => $this->changeRequest->title,
            'status'           => $this->changeRequest->status,
            'current_approver' => $this->currentApprover?->name,
            'step'             => $this->step,
            'total_steps'      => $this->totalSteps,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr  = $this->changeRequest;
        $msg = (new MailMessage)
            ->subject('📬 [CR Management System] CR Menunggu Review: ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Change Request Anda berhasil disubmit dan kini berada dalam antrean review oleh Approver.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Jenis Perubahan:** ' . strtoupper($cr->change_type))
            ->line('**Prioritas:** ' . strtoupper($cr->priority))
            ->line('**Layanan Terdampak:** ' . $cr->affected_service);

        if ($this->currentApprover) {
            $msg->line('**Approver (Saat ini):** ' . $this->currentApprover->name . ' (Tahap ' . $this->step . ' dari ' . $this->totalSteps . ')');
        }

        return $msg
            ->action('Lihat Detail CR', route('cr.show', $cr))
            ->line('Kami akan memberi tahu Anda segera setelah proses review dimulai.')
            ->line('Terima kasih.');
    }
}
