<?php

namespace App\Console\Commands;

use App\Models\ChangeRequest;
use App\Models\User;
use App\Notifications\ChangeRequestReminder;
use Illuminate\Console\Command;

class CrScheduleReminder extends Command
{
    protected $signature   = 'cr:schedule-reminder';
    protected $description = 'Kirim reminder email ke engineer, requester, dan approver sebelum jadwal implementasi';

    public function handle(): void
    {
        $now = now();

        // Ambil semua CR berstatus scheduled yang punya jadwal
        $crs = ChangeRequest::with(['schedule', 'pic', 'requester'])
            ->where('status', 'scheduled')
            ->whereHas('schedule')
            ->get();

        foreach ($crs as $cr) {
            $plannedStart = $cr->schedule->planned_start;
            $diffMinutes  = $now->diffInMinutes($plannedStart, false); // positif = masih akan datang

            // Tentukan tipe reminder berdasarkan sisa waktu
            $reminderType = null;
            if ($diffMinutes >= 2820 && $diffMinutes <= 2940) {
                // ~3 hari (2820-2940 menit = 47-49 jam)
                $reminderType = '3_days';
            } elseif ($diffMinutes >= 1380 && $diffMinutes <= 1500) {
                // ~1 hari (1380-1500 menit = 23-25 jam)
                $reminderType = '1_day';
            } elseif ($diffMinutes >= 60 && $diffMinutes <= 180) {
                // 1-3 jam sebelum
                $reminderType = 'few_hours';
            }

            if (!$reminderType) {
                continue;
            }

            $recipients = collect([$cr->requester, $cr->pic])->filter();
            $chainIds   = collect($cr->approver_chain ?? [])->filter()->unique();
            if ($chainIds->isNotEmpty()) {
                $recipients = $recipients->merge(User::whereIn('id', $chainIds)->get());
            }

            foreach ($recipients->unique('id') as $user) {
                $user->notify(new ChangeRequestReminder($cr, $reminderType));
            }

            $this->info('Reminder ' . $reminderType . ' dikirim untuk ' . $cr->cr_number);
        }
    }
}
