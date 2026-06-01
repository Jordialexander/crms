<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChangeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cr_number','title','description','reason','affected_service',
        'change_type','category','priority','risk_level','impact',
        'rollback_plan','status','requester_id','approver_id','pic_id',
        'submitted_at','approved_at','closed_at','closed_reason','rejection_note','cancellation_note','post_mortem_note','closing_note',
        'approver_chain','current_approver_id','current_approval_step',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
        'closed_at'    => 'datetime',
        'approver_chain' => 'array',
    ];

    protected $appends = ['status_badge','risk_badge','status_label'];

    public function requester()        { return $this->belongsTo(User::class,'requester_id'); }
    public function approver()         { return $this->belongsTo(User::class,'approver_id'); }
    public function currentApprover()  { return $this->belongsTo(User::class,'current_approver_id'); }
    public function pic()              { return $this->belongsTo(User::class,'pic_id'); }
    public function riskAssessment()   { return $this->hasOne(RiskAssessment::class); }
    public function approvals()        { return $this->hasMany(Approval::class); }
    public function schedule()         { return $this->hasOne(ChangeSchedule::class)->where('is_active', true)->latestOfMany(); }
    public function schedules()        { return $this->hasMany(ChangeSchedule::class)->orderBy('round'); }
    public function implementationLogs(){ return $this->hasMany(ImplementationLog::class); }
    public function attachments()      { return $this->hasMany(CrAttachment::class); }
    public function activityLogs()     { return $this->hasMany(CrActivityLog::class)->latest(); }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->status === 'closed') {
            return match($this->closed_reason) {
                'completed' => 'success',
                'failed'    => 'danger',
                'rejected'  => 'danger',
                'canceled'  => 'secondary',
                default     => 'secondary',
            };
        }

        return match($this->status) {
            'draft'             => 'secondary',
            'canceled'          => 'secondary',
            'need_review'       => 'info',
            'submitted'         => 'info',
            'under_review'      => 'warning',
            'waiting_approval'  => 'primary',
            'approved'          => 'success',
            'rejected'          => 'danger',
            'scheduled'         => 'primary',
            'in_progress'       => 'warning',
            'completed'         => 'success',
            'failed'            => 'danger',
            'rollback'          => 'dark',
            default             => 'secondary',
        };
    }

    public function getRiskBadgeAttribute(): string
    {
        return match($this->risk_level) {
            'low'    => 'success',
            'medium' => 'warning',
            'high'   => 'danger',
            default  => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'closed') {
            return match($this->closed_reason) {
                'completed' => 'CLOSED - SELESAI',
                'rejected'  => 'CLOSED - DITOLAK',
                'canceled'  => 'CLOSED - DIBATALKAN',
                'failed'    => 'CLOSED - GAGAL',
                default     => 'CLOSED',
            };
        }
        return match($this->status) {
            'approved'         => 'MENUNGGU JADWAL',
            'waiting_approval' => 'WAITING APPROVAL',
            'need_review'      => 'NEED REVIEW',
            'under_review'     => 'UNDER REVIEW',
            'in_progress'      => 'IN PROGRESS',
            'canceled'         => 'CANCELED',
            'rejected'         => 'REJECTED',
            default            => str_replace('_', ' ', strtoupper($this->status)),
        };
    }

    public static function generateCrNumber(): string
    {
        $year = now()->format('Y');
        $last = static::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByRaw('CAST(SUBSTRING_INDEX(cr_number, \'-\', -1) AS UNSIGNED) DESC')
            ->value('cr_number');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;
        return 'CR-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
