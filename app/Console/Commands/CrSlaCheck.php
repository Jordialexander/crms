<?php

namespace App\Console\Commands;

use App\Models\ChangeRequest;
use App\Models\User;
use App\Notifications\ChangeRequestSlaWarning;
use Illuminate\Console\Command;

class CrSlaCheck extends Command
{
    protected $signature   = 'cr:sla-check';
    protected $description = 'Cek CR in_progress yang melewati atau mendekati batas jadwal implementasi';

    public function handle(): void
    {
        $now = now();

        $crs = ChangeRequest::with(['schedule', 'pic', 'requester'])
            ->where('status', 'in_progress')
            ->whereHas('schedule')
            ->get();

        foreach ($crs as $cr) {
            $plannedEnd  = $cr->schedule->planned_end;
            $diffMinutes = $now->diffInMinutes($plannedEnd, false); // negatif = sudah lewat

            if ($diffMinutes < 0) {
                // Sudah melewati jadwal selesai
                $minutesOverdue = abs((int) $diffMinutes);
                // Kirim notif tiap 30 menit setelah overdue (mencegah spam tiap menit)
                if ($minutesOverdue % 30 !== 0 && $minutesOverdue > 30) {
                    continue;
                }
                $warningType = 'overdue';
            } elseif ($diffMinutes <= 30) {
                // 30 menit sebelum batas selesai
                $minutesOverdue = 0;
                $warningType    = 'approaching_end';
            } else {
                continue;
            }

            $recipients = collect([$cr->requester, $cr->pic])->filter();
            $chainIds   = collect($cr->approver_chain ?? [])->filter()->unique();
            if ($chainIds->isNotEmpty()) {
                $recipients = $recipients->merge(User::whereIn('id', $chainIds)->get());
            }

            foreach ($recipients->unique('id') as $user) {
                $user->notify(new ChangeRequestSlaWarning($cr, $warningType, $minutesOverdue ?? 0));
            }

            $label = $warningType === 'overdue' ? 'OVERDUE ' . $minutesOverdue . ' mnt' : 'approaching_end';
            $this->info('SLA warning [' . $label . '] dikirim untuk ' . $cr->cr_number);
        }
    }
}
