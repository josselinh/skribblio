<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteCurrentUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CurrentUserController extends Controller
{
    /**
     * The logged-in user, verified or not (the SPA needs it to know where to redirect).
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Deletes the current user's account. The database cascades delete everything they created (their groups
     * with all their words, their collections), their favorites, memberships and access requests; the words
     * they added to other people's groups are kept without an author. Their sessions, API tokens and password
     * reset tokens are deleted too, and the current session is closed.
     */
    public function destroy(DeleteCurrentUserRequest $request): Response
    {
        $user = $request->user();

        // Before deleting: logging out saves a new remember token, which would recreate the deleted user.
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            DB::table(config('auth.passwords.users.table'))->where('email', $user->email)->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table'))->where('user_id', $user->getKey())->delete();
            }
            $user->delete();
        });

        return response()->noContent();
    }
}
