<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunScheduledAccountBatchUpdates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get user from account to check date w/ timezone
        Log::info("Handling scheduled AccountBatchUpdates...");
        User::whereHas('accountBatchUpdates', fn($a) => $a->notDone())->get()->each(function($user) {
            $updates = $user->accountBatchUpdates()
                ->notDone()
                ->where('date', '<=', Carbon::now()->tz($user->timezone)->format('Y-m-d'))
                ->get();

            Log::info("For user {$user->id} ({$user->name}): {$updates->count()} updates: {$updates->pluck('id')}");

            $updates->each->handle();
        });
    }
}
