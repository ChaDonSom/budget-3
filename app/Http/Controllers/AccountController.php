<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchUpdateAccountRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountBatchUpdateRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\AccountBatchUpdate;
use App\Models\BatchUpdateAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Account::class, 'account');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $users = collect([$user])->concat($user->usersWhoSharedToMe)->concat($user->sharedUsers)->unique();
        return Account::query()
            ->whereIn('user_id', $users->pluck('id')->values())
            ->with([
                'favoritedUsers',
                // We _would_ like to see batchUpdates for index, but only the not-done ones
                'batchUpdates' => fn($query) => $query->notDone()
            ])
            ->get()
            ->keyBy('id');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAccountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAccountRequest $request)
    {
        $account = Account::create($request->validated());

        return $account;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $account)
    {
        return $account->load([
            'favoritedUsers',
            'batchUpdates' => fn($query) => $query->notDone()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAccountRequest  $request
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        $validated = $request->validated();

        $account->fill($validated)->save();

        $account->favoritedUsers()->sync(collect($validated['favorited_users'])->pluck('id'));

        $account->load(['favoritedUsers', 'batchUpdates' => fn($query) => $query->notDone()]);

        return $account;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        $account->delete();

        return response('Success', 200);
    }
}
