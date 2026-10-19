<?php

namespace Tests\Feature\Organization;

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Enums\Role;
use App\Modules\Organization\Models\Group;
use App\Modules\Organization\Models\Membership;
use App\Modules\Organization\Models\Patrol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_ids_are_generated_for_saved_models(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $patrol = Patrol::factory()->create(['group_id' => $group->id]);
        $membership = Membership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        foreach ([$user, $group, $patrol, $membership] as $model) {
            $this->assertTrue(Str::isUlid($model->public_id));
            $this->assertSame('public_id', $model->getRouteKeyName());
        }
    }

    public function test_group_patrol_membership_and_user_relationships_work(): void
    {
        $group = Group::factory()->create();
        $patrol = Patrol::factory()->create(['group_id' => $group->id]);
        $user = User::factory()->create();

        $membership = Membership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'patrol_id' => $patrol->id,
            'role' => Role::PatrolSecretary,
        ]);

        $this->assertTrue($group->patrols->contains($patrol));
        $this->assertTrue($group->memberships->contains($membership));
        $this->assertTrue($patrol->memberships->contains($membership));
        $this->assertTrue($membership->user->is($user));
        $this->assertTrue($membership->group->is($group));
        $this->assertTrue($membership->patrol->is($patrol));
        $this->assertTrue($user->memberships->contains($membership));
        $this->assertTrue($user->groups->contains($group));
        $this->assertSame(Role::PatrolSecretary, $membership->role);
    }

    public function test_for_group_scopes_filter_records_to_the_selected_group(): void
    {
        $firstGroup = Group::factory()->create();
        $secondGroup = Group::factory()->create();

        $firstPatrol = Patrol::factory()->create(['group_id' => $firstGroup->id]);
        Patrol::factory()->create(['group_id' => $secondGroup->id]);

        $firstMembership = Membership::factory()->create([
            'group_id' => $firstGroup->id,
        ]);
        Membership::factory()->create([
            'group_id' => $secondGroup->id,
        ]);

        $this->assertSame(
            [$firstGroup->id],
            Group::forGroup($firstGroup)->pluck('id')->all()
        );

        $this->assertSame(
            [$firstPatrol->id],
            Patrol::forGroup($firstGroup)->pluck('id')->all()
        );

        $this->assertSame(
            [$firstMembership->id],
            Membership::forGroup($firstGroup)->pluck('id')->all()
        );
    }

   public function test_user_platform_admin_flag_is_not_mass_assignable(): void
    {
        $user = User::factory()->create();
        $user->refresh();

        $this->assertFalse($user->is_platform_admin);
        $this->assertFalse($user->isFillable('is_platform_admin'));
        $this->assertFalse($user->isFillable('is_active'));

        $user->fill(['is_platform_admin' => true]);
        $user->save();
        $user->refresh();

        $this->assertFalse($user->is_platform_admin);
    }
    public function test_patrol_names_must_be_unique_within_a_group(): void
    {
        $group = Group::factory()->create();

        Patrol::factory()->create([
            'group_id' => $group->id,
            'name' => 'Falcon',
        ]);

        $this->expectException(QueryException::class);

        Patrol::factory()->create([
            'group_id' => $group->id,
            'name' => 'Falcon',
        ]);
    }

    public function test_a_user_can_only_have_one_membership_per_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        Membership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        $this->expectException(QueryException::class);

        Membership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);
    }
}
