<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeSchedule extends Model
{
    protected $fillable = [
        'change_request_id',
        'round',
        'is_active',
        'planned_start',
        'planned_end',
        'actual_start',
        'actual_end',
        'estimated_downtime_minutes',
        'pic_id',
        'notes',
        'reminder_sent_at',
        'overdue_warning_sent_at',
    ];

    protected $casts = [
        'planned_start'           => 'datetime',
        'planned_end'             => 'datetime',
        'actual_start'            => 'datetime',
        'actual_end'              => 'datetime',
        'reminder_sent_at'        => 'datetime',
        'overdue_warning_sent_at' => 'datetime',
    ];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }
}
