<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChangeRequest $changeRequest,
        public readonly ?string $note = null,
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
            'kind'      => 'cr_rejected',
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
            'status'    => $this->changeRequest->status,
            'note'      => $this->note,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        return (new MailMessage)
            ->subject('❌ [CR Management System] CR Ditolak: ' . $cr->cr_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Mohon maaf, Change Request Anda telah **ditolak** oleh Approver dengan catatan sebagai berikut:')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Catatan Penolakan:** ' . $this->note)
            ->line('**Apa yang harus Anda lakukan selanjutnya?**')
            ->line('- **Perbaiki dan Submit Ulang:** Anda dapat memperbaiki data pada CR ini (Edit Draft) dan melakukan Submit ulang untuk diapprove kembali.')
            ->line('- **Batalkan CR:** Jika CR ini memang sudah tidak diperlukan, Anda dapat membatalkannya secara permanen dari halaman detail.')
            ->action('Lihat Detail CR & Tindak Lanjut', route('cr.show', $cr));
    }
}
