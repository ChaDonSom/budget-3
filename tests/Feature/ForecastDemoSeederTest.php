<?php

namespace Tests\Feature;

use App\Models\AccountBatchUpdate;
use App\Models\User;
use Database\Seeders\ForecastDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForecastDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpTraits()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite is required for isolated seeder tests.');
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        return parent::setUpTraits();
    }

    public function test_it_seeds_repeatable_future_payments_for_a_demo_user()
    {
        app()->detectEnvironment(fn() => 'local');

        $seeder = new ForecastDemoSeeder();
        $seeder->run();
        $seeder->run();

        $user = User::where('email', 'forecast-demo@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertCount(7, $user->accounts);

        $updates = AccountBatchUpdate::where('user_id', $user->id)
            ->with('accounts')
            ->get();

        $this->assertCount(7, $updates);
        $this->assertSame(1, $updates->whereNull('weeks')->count());
        $this->assertSame(1, $updates->where('weeks', 2)->count());
        $this->assertSame(5, $updates->where('weeks', 4)->count());

        foreach ($updates as $update) {
            $this->assertNull($update->done_at);
            $this->assertGreaterThanOrEqual(now()->toDateString(), $update->date);
            $this->assertCount(1, $update->accounts);
            $this->assertLessThan(0, $update->accounts->first()->pivot->amount);
        }

        $response = $this->actingAs($user)->getJson('/api/accounts');
        $response->assertOk()->assertJsonCount(7);

        foreach ($response->json() as $account) {
            $this->assertNotEmpty($account['batch_updates']);
            $this->assertLessThan(
                0,
                $account['batch_updates'][0]['pivot']['amount']
            );
        }
    }
}
