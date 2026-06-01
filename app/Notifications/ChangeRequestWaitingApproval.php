<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestWaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * $forApprover = true  → dikirim ke approver yang perlu take action
     * $forApprover = false → dikirim ke requester sebagai update status
     */
    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly bool $forApprover = true,
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
            'kind'         => 'cr_waiting_approval',
            'cr_id'        => $this->changeRequest->id,
            'cr_number'    => $this->changeRequest->cr_number,
            'title'        => $this->changeRequest->title,
            'status'       => $this->changeRequest->status,
            'for_approver' => $this->forApprover,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr    = $this->changeRequest;
        $step  = $cr->current_approval_step ?? 1;
        $total = count($cr->approver_chain ?? []);
        $ra    = $cr->riskAssessment;

        if ($this->forApprover) {
            $msg = (new MailMessage)
                ->subject('[CR Management System] CR Menunggu Persetujuan Anda: ' . $cr->cr_number)
                ->greeting('Halo ' . $notifiable->name . ',')
                ->line('CR berikut telah selesai direview dan memerlukan **persetujuan Anda**.')
                ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
                ->line('**Pemohon:** ' . ($cr->requester->name ?? '-'))
                ->line('**Jenis Perubahan:** ' . strtoupper($cr->change_type))
                ->line('**Prioritas:** ' . strtoupper($cr->priority))
                ->line('**Layanan Terdampak:** ' . $cr->affected_service)
                ->line('**Tahap Approval:** ' . $step . ' dari ' . $total);

            if ($ra) {
                $msg->line('**Risk Level:** ' . strtoupper($ra->risk_level) . ' (Score: ' . $ra->total_score . ')');
            }

            return $msg
                ->action('Approve / Tolak Sekarang', route('cr.show', $cr))
                ->line('Terima kasih.');
        }

        // Untuk requester: update status saja
        return (new MailMessage)
            ->subject('[CR Management System] CR Anda Sedang Menunggu Approval: ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('CR Anda telah selesai direview dan saat ini sedang menunggu keputusan approver.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Tahap Approval:** ' . $step . ' dari ' . $total)
            ->action('Lihat Status CR', route('cr.show', $cr))
            ->line('Anda akan mendapat notifikasi saat keputusan telah diambil.')
            ->line('Terima kasih.');
    }
}
