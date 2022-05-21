<?php

namespace App\Policies;

use App\Models\AccountBatchUpdate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountBatchUpdatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountBatchUpdate  $accountBatchUpdate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AccountBatchUpdate $accountBatchUpdate)
    {
        return $this->hasOwnership($user, $accountBatchUpdate);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Account  $accountBatchUpdate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AccountBatchUpdate $accountBatchUpdate)
    {
        return $this->hasOwnership($user, $accountBatchUpdate);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountBatchUpdate  $accountBatchUpdate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, AccountBatchUpdate $accountBatchUpdate)
    {
        return $this->hasOwnership($user, $accountBatchUpdate);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountBatchUpdate  $accountBatchUpdate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, AccountBatchUpdate $accountBatchUpdate)
    {
        return $this->hasOwnership($user, $accountBatchUpdate);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountBatchUpdate  $accountBatchUpdate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AccountBatchUpdate $accountBatchUpdate)
    {
        return $this->hasOwnership($user, $accountBatchUpdate);
    }

    public function hasOwnership(User $user, AccountBatchUpdate $accountBatchUpdate): bool
    {
        return $accountBatchUpdate->user_id == $user->id
            || in_array(
                $accountBatchUpdate->user_id,
                collect([$user])->concat($user->sharedUsers)->concat($user->usersWhoSharedToMe)->pluck('id')->toArray()
            );
    }
}
