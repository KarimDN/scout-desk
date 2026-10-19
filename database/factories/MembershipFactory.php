<?php

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Enums\Role;
use App\Modules\Organization\Models\Group;
use App\Modules\Organization\Models\Membership;
use App\Modules\Organization\Models\Patrol;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Membership> */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'group_id' => Group::factory(),
            'patrol_id' => null,
            'role' => Role::GroupSecretary,
        ];
    }

    public function patrolSecretary(?Patrol $patrol = null): static
    {
        return $this->state(fn () => [
            'patrol_id' => $patrol?->getKey() ?? Patrol::factory(),
            'role' => Role::PatrolSecretary,
        ]);
    }
}
