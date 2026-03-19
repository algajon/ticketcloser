<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptTemplate extends Model
{
    protected $fillable = ['name', 'assistant_type', 'base_markdown', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function versions(): HasMany
    {
        return $this->hasMany(PromptVersion::class);
    }
}
