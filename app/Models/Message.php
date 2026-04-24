<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['agency_id', 'conversation_id', 'sender_type', 'content', 'confidence', 'metadata'])]
class Message extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'metadata' => 'array',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
