<?php

namespace Tests\Feature;

use Database\Seeders\ForecastDemoSeeder;
use Tests\TestCase;

class ForecastDemoSeederEnvironmentTest extends TestCase
{
    public function test_it_refuses_to_seed_outside_local_environment()
    {
        app()->detectEnvironment(fn() => 'production');

        $this->expectException(\RuntimeException::class);
        (new ForecastDemoSeeder())->run();
    }
}
