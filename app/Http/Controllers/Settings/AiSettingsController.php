<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiSettingsUpdateRequest;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $agency = $request->user()->agency;

        abort_if($agency === null, HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'User is not assigned to an agency.');

        return Inertia::render('settings/AiSettings', [
            'settings' => [
                'ai_provider' => $agency->ai_provider,
                'ai_confidence_threshold' => (float) $agency->ai_confidence_threshold,
                'ai_auto_handoff' => (bool) $agency->ai_auto_handoff,
            ],
        ]);
    }

    public function update(AiSettingsUpdateRequest $request): RedirectResponse
    {
        $agency = Agency::query()->findOrFail($request->user()->agency_id);

        $agency->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI settings updated.')]);

        return to_route('ai-settings.edit');
    }
}
