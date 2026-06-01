<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * $isFinalApproval = true → semua tahap selesai, CR menunggu jadwal engineer
     * $isFinalApproval = false → tahap ini selesai, diteruskan ke approver berikutnya
     */
    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly bool $isFinalApproval = true,
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
            'kind'              => 'cr_approved',
            'cr_id'             => $this->changeRequest->id,
            'cr_number'         => $this->changeRequest->cr_number,
            'title'             => $this->changeRequest->title,
            'status'            => $this->changeRequest->status,
            'is_final_approval' => $this->isFinalApproval,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;

        if ($this->isFinalApproval) {
            return (new MailMessage)
                ->subject('✅ [CR Management System] CR Disetujui: ' . $cr->cr_number)
                ->greeting('Halo ' . $notifiable->name . ',')
                ->line('Kabar baik! Change Request Anda telah **disetujui** secara penuh oleh semua approver.')
                ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
                ->line('**Jenis Perubahan:** ' . strtoupper($cr->change_type))
                ->line('**Prioritas:** ' . strtoupper($cr->priority))
                ->line('CR ini sekarang sedang **menunggu jadwal implementasi** dari PIC yang ditugaskan.')
                ->action('Lihat Detail CR', route('cr.show', $cr))
                ->line('Anda akan mendapat pemberitahuan lebih lanjut saat jadwal implementasi sudah ditetapkan. Terima kasih.');
        }

        return (new MailMessage)
            ->subject('⏳ [CR Management System] CR Disetujui (Tahap ' . $this->step . '): ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Change Request Anda telah disetujui pada **Tahap ' . $this->step . ' dari ' . $this->totalSteps . '**.')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('Saat ini, CR diteruskan ke approver tahap berikutnya (Tahap ' . ($this->step + 1) . ').')
            ->action('Lihat Detail CR', route('cr.show', $cr))
            ->line('Terima kasih.');
    }
}
