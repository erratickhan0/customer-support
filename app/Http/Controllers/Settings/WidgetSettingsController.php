<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AgencyApiKey;
use App\Services\Agency\UserAgencyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WidgetSettingsController extends Controller
{
    public function edit(Request $request, UserAgencyResolver $agencyResolver): Response
    {
        $agency = $agencyResolver->resolve($request->user());
        $apiKey = $agency->apiKeys()
            ->where('is_active', true)
            ->latest()
            ->first();

        $plainApiKey = $request->session()->get('widget_api_key');
        $scriptUrl = url('/widget.js');

        return Inertia::render('settings/Widget', [
            'widget' => [
                'has_api_key' => $apiKey !== null,
                'api_key' => $plainApiKey,
                'key_name' => $apiKey?->name,
                'last_used_at' => $apiKey?->last_used_at?->toIso8601String(),
                'script_url' => $scriptUrl,
                'embed_code' => $plainApiKey
                    ? '<script src="'.$scriptUrl.'" data-api-key="'.$plainApiKey.'" async></script>'
                    : null,
            ],
        ]);
    }

    public function store(Request $request, UserAgencyResolver $agencyResolver): RedirectResponse
    {
        $agency = $agencyResolver->resolve($request->user());
        $plainApiKey = 'wk_live_'.Str::random(40);

        $agency->apiKeys()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        AgencyApiKey::query()->create([
            'agency_id' => $agency->id,
            'name' => 'Website Widget',
            'key_hash' => hash('sha256', $plainApiKey),
            'is_active' => true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Widget key generated. Copy it now; it will only be shown once.'),
        ]);

        return to_route('widget-settings.edit')
            ->with('widget_api_key', $plainApiKey);
    }
}
