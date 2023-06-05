<?php

namespace App\Models;

use App\Events\AccountBatchUpdateSaved;
use App\Notifications\AccountBatchUpdateHandledNotification;
use App\Notifications\FavoriteAccountUpdatedNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AccountBatchUpdate extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $fillable = [
        'date',
        'batch',
        'done_at',
        'user_id',
        'notify_me',
        'weeks',
        'note',
        'next_recurrence_id',
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
        $accounts = $this->accounts()->get();
        $batch = DB::select('select batch from audits where batch is not null order by created_at desc limit 1');
        // If nothing has been done yet, a blank slate...
        if (!count($batch)) $batch = 1;
        else $batch = $batch[0]->batch + 1;

        foreach ($accounts as $account) {
            if (!$forward) Log::info("Reversing changes for account {$account->name}, {$account->pivot->amount}");
            else Log::info("Making changes for account {$account->name}, {$account->pivot->amount}");
            Cache::put("account-batch-update-batch-{$account->pivot->id}", $batch, 60);
            Cache::put("account-batch-update-batch-{$account->pivot->id}-batch-id", $this->id, 60);
            $amount = $forward
                ? $account->amount + $account->pivot->amount
                : $account->amount - $account->pivot->amount;
            $account->fill(['amount' => $amount])->save();
        }

        $this->fill([
            'batch' => $batch,
            'done_at' => $forward ? Carbon::now() : null
        ])->save();

        if ($forward && $this->notify_me) {
            $this->user->notify((new AccountBatchUpdateHandledNotification($this, $accounts))->delay(
                app()->runningInConsole()
                    ? now()->addHours(10) // 10 am rather than 12 am midnight
                    : now()
            ));
        }

        if ($forward && $this->accounts->flatMap(fn($a) => $a->favoritedUsers)->count()) {
            foreach ($this->accounts as $account) {
                Notification::send(
                    $account->favoritedUsers->filter(fn($u) => $u->id != $this->user_id),
                    new FavoriteAccountUpdatedNotification($this, $account)
                );
            }
        }

        AccountBatchUpdateSaved::dispatch($this);

        // If this update has a `weeks` value defined, we can recur it by creating a new one x months out
        if ($forward && $this->weeks && $this->weeks >= 4) {
            if ($this->nextRecurrence && !$this->nextRecurrence->done_at) {
                // We need to update the next recurrence to match
                Log::debug("Updating next recurrence {$this->nextRecurrence->id} to match {$this->id}");
                $this->nextRecurrence->fill([
                    'date' => Carbon::parse($this->date)->addMonths($this->weeks / 4)->format('Y-m-d'),
                    'done_at' => null,
                ])->save();
                $this->nextRecurrence->accounts()->sync(
                    $this->accounts->mapWithKeys(fn($a) => [$a->id => ['amount' => $a->pivot->amount]])
                );
            } else {
                // We need to create a new one
                Log::debug("Creating new next recurrence for {$this->id}");
                $newUpdate = $this->replicate();
                $newUpdate->fill([
                    'date' => Carbon::parse($this->date)->addMonths($this->weeks / 4)->format('Y-m-d'),
                    'done_at' => null,
                ])->save();
                $newUpdate->accounts()->sync(
                    $this->accounts->mapWithKeys(fn($a) => [$a->id => ['amount' => $a->pivot->amount]])
                );
                $this->fill(['next_recurrence_id' => $newUpdate->id])->save();
            }
        }

        return $accounts;
    }

    public function nextRecurrence() {
        return $this->belongsTo(AccountBatchUpdate::class, 'next_recurrence_id');
    }

    public function previousRecurrence() {
        return $this->hasOne(AccountBatchUpdate::class, 'next_recurrence_id');
    }

    // TODO: a 'reverse' function, that way we can use it to posthumously edit the batchupdate to undo and have it go in later
    public function reverse() {
        return $this->handle(false);
    }

    public function scopeNotDone($query) {
        $query->whereNull('done_at');
    }
}
