<?php

namespace App\Console\Commands;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Console\Command;
use App\Notifications\ChangeRequestOverdueWarning;

class CheckOverdueImplementations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cr:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for CRs that are in_progress but have passed their planned end time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find CRs that are in progress and overdue
        $crs = ChangeRequest::where('status', 'in_progress')
            ->whereHas('schedule', function($q) {
                $q->where('is_active', true)
                  ->where('planned_end', '<', now())
                  ->whereNull('overdue_warning_sent_at');
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
                $recipient->notify(new ChangeRequestOverdueWarning($cr));
            }

            // Tandai sudah diingatkan agar tidak duplikat di next cron
            $cr->schedule->update(['overdue_warning_sent_at' => now()]);
            $count++;
        }

        $this->info("Sent overdue warnings for {$count} CR(s).");
    }
}
