<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class AccountBatchUpdate extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    public $fillable = [
        'date',
        'batch',
        'done_at',
        'user_id',
    ];

    public $casts = [
        'done_at' => 'date'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function accounts() {
        return $this->belongsToMany(Account::class)->withPivot(['amount']);
    }

    /**
     * Handle the batch update. Update all accounts, log the batch number to the audit, etc
     *
     * @return Collection<Account>
     */
    public function handle() {
        foreach ($this->accounts as $account) {
            Cache::put("account-batch-update-batch-{$account->pivot->id}", $this->batch, 60);
            $account->fill(['amount' => $account->amount + $account->pivot->amount])->save();
        }

        $this->fill(['done_at' => Carbon::now()])->save();

        // TODO: send notifications? Or schedule them for later, not midnight

        return $this->accounts;
    }

    public function scopeNotDone($query) {
        $query->whereNull('done_at');
    }
}
