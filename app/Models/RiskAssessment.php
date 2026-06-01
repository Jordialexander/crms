<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskAssessment extends Model
{
    protected $fillable = [
        'change_request_id',
        'impact_score',
        'complexity_score',
        'user_impact_score',
        'failure_probability_score',
        'total_score',
        'risk_level',
        'notes',
        'assessed_by',
    ];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public static function calculateRiskLevel(int $total): string
    {
        if ($total <= 8)  return 'low';
        if ($total <= 14) return 'medium';
        return 'high';
    }
}
