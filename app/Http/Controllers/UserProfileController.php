<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoveSharedUserRequest;
use App\Http\Requests\ShareBySearchRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function shareBySearch(ShareBySearchRequest $request) {
        $user = $request->user();

        $searchedUser = User::whereName($request->search)->orWhere('email', $request->search)->firstOrFail();

        if (!$user->sharedUsers()->find($searchedUser->id)) $user->sharedUsers()->attach($searchedUser);

        return response($user->load(['sharedUsers', 'usersWhoSharedToMe']), 200);
    }

    public function removeSharedUser(RemoveSharedUserRequest $request) {
        $user = $request->user();

        $user->sharedUsers()->sync($request->shared_users);

        return response($user->load(['sharedUsers', 'usersWhoSharedToMe']), 200);
    }
}
