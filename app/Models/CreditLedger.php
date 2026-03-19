<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditLedger extends Model
{
    protected $fillable = ['workspace_id', 'type', 'amount', 'meta'];

    protected $casts = [
        'workspace_id' => 'integer',
        'amount' => 'integer',
        'meta' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
