<?php

namespace App\Console\Commands;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Console\Command;
use App\Notifications\ChangeRequestUpcomingReminder;

class CheckUpcomingSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cr:check-upcoming';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for CRs that are approaching their scheduled start time (within 24 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find CRs that are scheduled and haven't started yet
        $crs = ChangeRequest::where('status', 'scheduled')
            ->whereHas('schedule', function($q) {
                $q->where('is_active', true)
                  ->where('planned_start', '<=', now()->addHours(24))
                  ->where('planned_start', '>', now())
                  // Kita tambah flag agar tidak dikirim berulang (optional, tapi best practice)
                  ->whereNull('reminder_sent_at');
            })
            ->with(['schedule', 'requester', 'pic'])
            ->get();

        $count = 0;
        foreach ($crs as $cr) {
            // Notifikasi ke Requester, PIC, dan semua Approver
            $recipients = collect([$cr->requester, $cr->pic])->filter();
            $chainIds   = collect($cr->approver_chain ?? [])->filter()->unique();
            if ($chainIds->isNotEmpty()) {
                $recipients = $recipients->merge(User::whereIn('id', $chainIds)->get());
            }

            foreach ($recipients->unique('id') as $recipient) {
                $recipient->notify(new ChangeRequestUpcomingReminder($cr));
            }

            // Tandai sudah diingatkan agar tidak duplikat di next cron
            $cr->schedule->update(['reminder_sent_at' => now()]);
            $count++;
        }

        $this->info("Sent upcoming schedule reminders for {$count} CR(s).");
    }
}
