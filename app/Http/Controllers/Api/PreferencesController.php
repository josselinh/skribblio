<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Resources\UserResource;

class PreferencesController extends Controller
{
    /**
     * Updates the current user's theme and email preferences, keeping the kinds of emails not given.
     */
    public function update(UpdatePreferencesRequest $request): UserResource
    {
        $user = $request->user();
        $validated = $request->validated();

        if (array_key_exists('theme', $validated)) {
            $user->theme = $validated['theme'];
        }
        if (array_key_exists('email_notifications', $validated)) {
            $user->email_notifications = [
                ...$user->email_notifications ?? [],
                ...array_map(fn (mixed $isWanted): bool => (bool) $isWanted, $validated['email_notifications']),
            ];
        }
        $user->save();

        return new UserResource($user);
    }
}
