<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantPreset extends Model
{
    protected $fillable = [
        'key',
        'name',
        'vapi_payload_json',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'vapi_payload_json' => 'array',
    ];
}
