<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TemplatePolicy
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
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Template $template)
    {
        return $this->hasOwnership($user, $template);
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
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Template $template)
    {
        return $this->hasOwnership($user, $template);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Template $template)
    {
        return $this->hasOwnership($user, $template);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Template $template)
    {
        return $this->hasOwnership($user, $template);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Template $template)
    {
        return $this->hasOwnership($user, $template);
    }

    public function hasOwnership(User $user, Template $template): bool
    {
        return $template->user_id == $user->id
            || in_array(
                $template->user_id,
                collect([$user])->concat($user->sharedUsers)->concat($user->usersWhoSharedToMe)->pluck('id')->toArray()
            );
    }
}
