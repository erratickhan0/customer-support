<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'is_active', 'ai_provider', 'ai_confidence_threshold', 'ai_auto_handoff'])]
class Agency extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'ai_confidence_threshold' => 'float',
            'ai_auto_handoff' => 'bool',
        ];
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(AgencyApiKey::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function knowledgeDocuments(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class);
    }
}
