<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Players\Actions\ChangeAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $profile = $request->user()?->playerProfile;

        return Inertia::render('settings/Profile', [
            'playerProfile' => $profile === null ? null : [
                'username' => $profile->username,
                'country_code' => $profile->country_code,
                'preferred_locale' => $profile->preferred_locale,
            ],
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail
                && config('luxbet.require_email_verification') === true,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill(array_intersect_key($validated, array_flip(['name', 'email'])));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $profile = $user->playerProfile;

        if ($profile !== null) {
            $profile->fill(array_intersect_key($validated, array_flip(['username', 'country_code', 'preferred_locale'])));
            $profile->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request, ChangeAccountStatus $changeStatus): RedirectResponse
    {
        $user = $request->user();

        // Accounts are closed rather than hard-deleted so the audit trail
        // stays intact. Data retention and erasure follow the privacy
        // procedures in docs/security.md.
        $changeStatus($user, UserStatus::Closed, 'Closed by the account holder.', actor: $user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
