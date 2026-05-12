<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiSettingsUpdateRequest;
use App\Services\Agency\UserAgencyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function edit(Request $request, UserAgencyResolver $agencyResolver): Response
    {
        $agency = $agencyResolver->resolve($request->user());

        return Inertia::render('settings/AiSettings', [
            'settings' => [
                'ai_provider' => $agency->ai_provider,
                'ai_confidence_threshold' => (float) $agency->ai_confidence_threshold,
                'ai_auto_handoff' => (bool) $agency->ai_auto_handoff,
            ],
        ]);
    }

    public function update(AiSettingsUpdateRequest $request, UserAgencyResolver $agencyResolver): RedirectResponse
    {
        $agency = $agencyResolver->resolve($request->user());

        $agency->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI settings updated.')]);

        return to_route('ai-settings.edit');
    }
}
