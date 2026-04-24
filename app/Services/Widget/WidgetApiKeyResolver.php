<?php

namespace App\Services\Widget;

use App\Models\Agency;
use App\Models\AgencyApiKey;
use Illuminate\Support\Carbon;

class WidgetApiKeyResolver
{
    public function resolveAgency(string $apiKey): ?Agency
    {
        $apiKeyRecord = AgencyApiKey::query()
            ->where('key_hash', hash('sha256', $apiKey))
            ->where('is_active', true)
            ->with('agency')
            ->first();

        if (! $apiKeyRecord || ! $apiKeyRecord->agency?->is_active) {
            return null;
        }

        $apiKeyRecord->forceFill(['last_used_at' => Carbon::now()])->save();

        return $apiKeyRecord->agency;
    }
}
