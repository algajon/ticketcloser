<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'workspace_id', 'name', 'phone_e164', 'email', 'property_code', 'unit', 'notes'
    ];
}
