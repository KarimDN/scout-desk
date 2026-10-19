<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Group> */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Scout Group',
        ];
    }
}
