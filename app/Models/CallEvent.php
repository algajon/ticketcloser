<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallEvent extends Model
{
    protected $fillable = [
        'workspace_id','queue_id','vapi_call_id','from_number','to_number','duration_seconds','cost','transcript','recording_url','meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
