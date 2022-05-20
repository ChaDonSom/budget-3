<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
    public function handle($forward = true) {
        $batch = DB::select('select batch from audits where batch is not null order by created_at desc limit 1');
        // If nothing has been done yet, a blank slate...
        if (!count($batch)) $batch = 1;
        else $batch = $batch[0]->batch + 1;

        foreach ($this->accounts as $account) {
            Cache::put("account-batch-update-batch-{$account->pivot->id}", $batch, 60);
            Cache::put("account-batch-update-batch-{$account->pivot->id}-batch-id", $this->id, 60);
            $amount = $forward
                ? $account->amount + $account->pivot->amount
                : $account->amount - $account->pivot->amount;
            $account->fill(['amount' => $amount])->save();
        }

        $this->fill([
            'batch' => $batch,
            'done_at' => Carbon::now()
        ])->save();

        // TODO: send notifications? Or schedule them for later, not now (midnight)

        return $this->accounts;
    }

    // TODO: a 'reverse' function, that way we can use it to posthumously edit the batchupdate to undo and have it go in later
    public function reverse() {
        return $this->handle(false);
    }

    public function scopeNotDone($query) {
        $query->whereNull('done_at');
    }
}
