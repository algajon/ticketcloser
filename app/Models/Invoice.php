<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'workspace_id',
        'stripe_invoice_id',
        'amount_due',
        'amount_paid',
        'currency',
        'status',
        'hosted_invoice_url',
        'invoice_pdf',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** Amount in dollars formatted */
    public function formattedAmount(): string
    {
        return '$' . number_format($this->amount_due / 100, 2);
    }
}
