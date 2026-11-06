<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Audit\AuditLogger;
use App\Domain\ResponsiblePlay\Models\ResponsiblePlaySetting;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResponsiblePlayController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $settings = ResponsiblePlaySetting::forUser($user);

        return Inertia::render('settings/ResponsiblePlay', [
            'reminderMinutes' => $settings->demo_session_reminder_minutes,
            'marketingOptIn' => $settings->marketing_opt_in,
            'reminderOptions' => ResponsiblePlaySetting::REMINDER_OPTIONS,
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'demo_session_reminder_minutes' => ['nullable', 'integer', Rule::in(ResponsiblePlaySetting::REMINDER_OPTIONS)],
            'marketing_opt_in' => ['required', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $settings = ResponsiblePlaySetting::forUser($user);
        $before = ['reminder' => $settings->demo_session_reminder_minutes, 'marketing' => $settings->marketing_opt_in];

        $settings->forceFill(['user_id' => $user->id])->fill($validated)->save();

        if ($before['marketing'] !== $settings->marketing_opt_in) {
            // Marketing consent changes are recorded so there is evidence of the preference.
            $audit->record('player.marketing_preference_changed', subject: $user, actor: $user, metadata: [
                'from' => $before['marketing'],
                'to' => $settings->marketing_opt_in,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsible play settings saved.']);

        return to_route('responsible-play.edit');
    }
}
