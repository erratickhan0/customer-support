<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['agency_id', 'name', 'key_hash', 'is_active', 'last_used_at'])]
class AgencyApiKey extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'last_used_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
