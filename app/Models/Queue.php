<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    protected $fillable = ['workspace_id', 'name', 'is_active', 'default_priority', 'business_hours'];

    protected $casts = [
        'is_active' => 'bool',
        'business_hours' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
