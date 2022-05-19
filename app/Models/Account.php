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

    public function accountHolder() {
        return $this->belongsTo(AccountHolder::class);
    }

    public function batchUpdates() {
        return $this->belongsToMany(AccountBatchUpdate::class)->withPivot(['amount']);
    }
}
