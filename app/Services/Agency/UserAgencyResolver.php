<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Str;

class UserAgencyResolver
{
    public function resolve(User $user): Agency
    {
        if ($user->agency) {
            return $user->agency;
        }

        return $this->createForUser($user);
    }

    public function createForUser(User $user): Agency
    {
        $agencyName = $user->name."'s Workspace";

        $agency = Agency::query()->create([
            'name' => $agencyName,
            'slug' => $this->uniqueSlug($agencyName),
            'is_active' => true,
            'ai_provider' => 'openai',
            'ai_confidence_threshold' => 0.50,
            'ai_auto_handoff' => true,
        ]);

        $user->forceFill(['agency_id' => $agency->id])->save();
        $user->setRelation('agency', $agency);

        return $agency;
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'workspace';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (Agency::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
