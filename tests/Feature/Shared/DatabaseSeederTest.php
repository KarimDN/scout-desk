<?php

namespace Tests\Feature\Shared;

use App\Modules\Identity\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_runs_and_users_get_public_ids(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->firstOrFail();

        $this->assertSame(26, strlen($user->public_id));
    }
}