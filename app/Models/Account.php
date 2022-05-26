<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Account extends Model implements Auditable
{
    use HasFactory;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'amount',
        'user_id',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function batchUpdates() {
        return $this->belongsToMany(AccountBatchUpdate::class)->withPivot(['amount']);
    }

    public function favoritedUsers() {
        return $this->belongsToMany(User::class, 'account_favorited_user', 'account_id', 'favorited_user_id');
    }
}
