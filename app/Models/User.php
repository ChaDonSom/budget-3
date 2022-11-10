<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $pushNotificationType = "users"; // Used in PusherChannel to form $beamsClient->'publishToUsers'

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'beta_opt_in',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function accounts() {
        return $this->hasMany(Account::class);
    }

    public function accountBatchUpdates() {
        return $this->hasMany(AccountBatchUpdate::class);
    }

    public function sharedUsers() {
        return $this->belongsToMany(User::class, 'user_shared_user', 'user_id', 'shared_user_id');
    }

    public function usersWhoSharedToMe() {
        return $this->belongsToMany(User::class, 'user_shared_user', 'shared_user_id', 'user_id');
    }
}
