<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrAttachment extends Model
{
    protected $fillable = [
        'change_request_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
