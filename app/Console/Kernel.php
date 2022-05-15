<?php

namespace App\Console;

use App\Models\AccountBatchUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        /**
         * 
         * Make scheduled updates to the user's account on the date they specified
         * 
         */
        $schedule->call(function () {
            // Get user from account to check date w/ timezone
            Log::info("Handling scheduled AccountBatchUpdates...");
            User::whereHas('accountBatchUpdates', fn($a) => $a->notDone())->get()->each(function($user) {
                $updates = $user->accountBatchUpdates()
                    ->where('date', '<=', Carbon::now()->tz($user->timezone)->format('Y-m-d'))
                    ->get();

                Log::info("For user {$user->id} ({$user->name}): {$updates->count()} updates");

                $updates->each->handle();
            });
        })->everyThirtyMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
