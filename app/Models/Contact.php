<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'workspace_id', 'name', 'phone_e164', 'email', 'property_code', 'unit', 'notes'
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function cases()
    {
        return $this->hasMany(SupportCase::class);
    }
}
