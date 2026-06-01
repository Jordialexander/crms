<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestImplementationCompleted extends Notification implements ShouldQueue
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
        $kind = match($this->changeRequest->status) {
            'completed' => 'cr_implementation_done',
            'failed'    => 'cr_implementation_failed',
            'rollback'  => 'cr_implementation_rollback',
            default     => 'cr_implementation_done',
        };

        return [
            'kind'      => $kind,
            'cr_id'     => $this->changeRequest->id,
            'cr_number' => $this->changeRequest->cr_number,
            'title'     => $this->changeRequest->title,
            'status'    => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr  = $this->changeRequest;
        $log = $cr->implementationLogs->last();

        $statusLabel = match($cr->status) {
            'completed' => '✅ BERHASIL',
            'failed'    => '💥 GAGAL',
            'rollback'  => '⚠️ ROLLBACK',
            default     => strtoupper($cr->status),
        };

        $subject = match($cr->status) {
            'completed' => '🎉 [CR Management System] Implementasi Selesai (Berhasil): ' . $cr->cr_number,
            'failed'    => '💥 [CR Management System] Implementasi Selesai (Gagal): ' . $cr->cr_number,
            'rollback'  => '⚠️ [CR Management System] Implementasi Selesai (Rollback): ' . $cr->cr_number,
            default     => '✔️ [CR Management System] Implementasi Selesai: ' . $cr->cr_number,
        };

        $msg = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Implementasi Change Request telah selesai dikerjakan dengan status akhir: **' . $statusLabel . '**')
            ->line('**CR:** ' . $cr->cr_number . ' — ' . $cr->title)
            ->line('**Engineer (PIC):** ' . ($cr->pic->name ?? '-'));

        if ($log) {
            if ($log->actual_start) {
                $msg->line('**Mulai Aktual:** ' . $log->actual_start->format('d M Y, H:i'));
            }
            if ($log->actual_end) {
                $msg->line('**Selesai Aktual:** ' . $log->actual_end->format('d M Y, H:i'));
            }
            if ($log->result_note) {
                $msg->line('**Hasil / Catatan PIC:** ' . $log->result_note);
            }
            if ($log->issues) {
                $msg->line('**Kendala yang Dihadapi:** ' . $log->issues);
            }
        }

        if (in_array($cr->status, ['failed', 'rollback'])) {
            $msg->line('Engineer wajib mengisi dokumen **Analisis Post-Mortem** agar Approver L1 dapat mengambil keputusan selanjutnya (Tutup CR atau Izinkan Reschedule).');
        } else {
            $msg->line('Silakan verifikasi hasil implementasi, dan jika sudah sesuai, CR dapat ditutup secara permanen.');
        }

        return $msg
            ->action('Lihat Detail Log & CR', route('cr.show', $cr))
            ->line('Terima kasih atas partisipasi Anda dalam proses perubahan ini.');
    }
}
