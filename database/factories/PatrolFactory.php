<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Group;
use App\Modules\Organization\Models\Patrol;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patrol> */
class PatrolFactory extends Factory
{
    protected $model = Patrol::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => fake()->unique()->word().' Patrol',
        ];
    }
}
