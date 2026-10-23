<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountBatchUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ForecastDemoSeeder extends Seeder
{
    public function run()
    {
        if (!app()->environment('local')) {
            throw new RuntimeException('Forecast demo data can only be seeded in the local environment.');
        }

        $user = User::updateOrCreate(
            ['email' => 'forecast-demo@example.test'],
            [
                'name' => 'Forecast Demo',
                'password' => Hash::make('password'),
            ]
        );

        $nextPayday = Carbon::today()->next(Carbon::THURSDAY);
        $firstOfNextMonth = Carbon::today()->startOfMonth()->addMonthNoOverflow();

        $payments = [
            ['rent', 'Demo - Rent', -90000, $firstOfNextMonth, 4],
            ['car', 'Demo - Car payment', -32000, $nextPayday, 4],
            ['phone', 'Demo - Phone', -8500, $nextPayday->copy()->addWeek(), 4],
            ['insurance', 'Demo - Insurance', -12500, $nextPayday->copy()->addWeeks(2), 4],
            ['power', 'Demo - Power', -14000, $nextPayday, 2],
            ['internet', 'Demo - Internet', -6500, $nextPayday->copy()->addWeeks(3), 4],
            ['fee', 'Demo - One-time fee', -4500, $nextPayday->copy()->addWeeks(5), null],
        ];

        foreach ($payments as [$key, $name, $amount, $date, $weeks]) {
            $account = Account::updateOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                ['amount' => 0, 'note' => '']
            );

            $update = AccountBatchUpdate::updateOrCreate(
                ['user_id' => $user->id, 'note' => "forecast-demo:{$key}"],
                [
                    'batch' => 0,
                    'date' => $date->toDateString(),
                    'done_at' => null,
                    'notify_me' => false,
                    'weeks' => $weeks,
                ]
            );

            $update->accounts()->sync([$account->id => ['amount' => $amount]]);
        }
    }
}
