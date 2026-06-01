<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImplementationLog extends Model
{
    protected $fillable = [
        'change_request_id',
        'implementer_id',
        'actual_start',
        'actual_end',
        'result_status',
        'result_note',
        'issues',
        'evidence_file',
        'post_review_note',
    ];

    protected $casts = [
        'actual_start' => 'datetime',
        'actual_end'   => 'datetime',
    ];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function implementer()
    {
        return $this->belongsTo(User::class, 'implementer_id');
    }
}
