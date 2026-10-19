<?php

namespace Tests\Feature\Organization;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Identity\Enums\AccountTokenPurpose;
use App\Modules\Identity\Models\AccountToken;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Enums\Role;
use App\Modules\Organization\Models\Group;
use App\Modules\Organization\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: string}
     */
    private function install(array $options): array
    {
        $code = Artisan::call('scoutdesk:install', $options);

        return [$code, Artisan::output()];
    }

    private function plainTokenFrom(string $output): string
    {
        $this->assertSame(1, preg_match('~/invitations/([A-Za-z0-9]+)~', $output, $matches), 'No setup link in the output');

        return $matches[1];
    }

    private function groupWithSecretary(bool $active): Group
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();

        if (! $active) {
            $user->forceFill(['is_active' => false])->save();
        }

        Membership::create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'role' => Role::GroupSecretary,
        ]);

        return $group;
    }

    public function test_install_creates_the_group_and_one_hashed_secretary_invitation(): void
    {
        [$code, $output] = $this->install([
            '--group' => 'Beirut First Group',
            '--email' => '  Secretary@Example.ORG ',
        ]);

        $this->assertSame(0, $code);
        $this->assertSame(1, Group::count());
        $this->assertSame('Beirut First Group', Group::first()->name);

        $plain = $this->plainTokenFrom($output);
        $token = AccountToken::sole();

        $this->assertSame(AccountTokenPurpose::Invitation, $token->purpose);
        $this->assertSame('group_secretary', $token->role);
        $this->assertSame('secretary@example.org', $token->email);
        $this->assertSame(Group::first()->id, $token->group_id);
        $this->assertNull($token->patrol_id);
        $this->assertNull($token->user_id);
        $this->assertSame(hash('sha256', $plain), $token->token_hash);
        $this->assertTrue($token->isUsable());

        // No account exists until the link is used (Task 2.7).
        $this->assertSame(0, User::count());
        $this->assertSame(0, Membership::count());
    }

    public function test_install_is_audited_without_secrets(): void
    {
        [, $output] = $this->install(['--group' => 'G', '--email' => 'sec@example.org']);

        $plain = $this->plainTokenFrom($output);
        $entry = AuditLog::sole();

        $this->assertSame('setup.install_run', $entry->action);
        $this->assertSame(Group::first()->id, $entry->group_id);
        $this->assertSame(['group_created' => true, 'reissued' => false], $entry->metadata);
        $this->assertStringNotContainsString($plain, json_encode($entry->toArray()));
    }

    public function test_install_refuses_when_an_active_group_secretary_exists(): void
    {
        $this->groupWithSecretary(active: true);

        [$code, $output] = $this->install(['--group' => 'Other', '--email' => 'new@example.org']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('already complete', $output);
        $this->assertSame(1, Group::count());
        $this->assertSame(0, AccountToken::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_rerunning_revokes_the_old_link_and_issues_a_new_one(): void
    {
        [, $firstOutput] = $this->install(['--group' => 'G', '--email' => 'a@example.org']);
        [$code, $secondOutput] = $this->install(['--email' => 'b@example.org']);

        $firstPlain = $this->plainTokenFrom($firstOutput);
        $secondPlain = $this->plainTokenFrom($secondOutput);

        $this->assertSame(0, $code);
        $this->assertNotSame($firstPlain, $secondPlain);
        $this->assertSame(1, Group::count());
        $this->assertSame(2, AccountToken::count());

        $first = AccountToken::where('token_hash', hash('sha256', $firstPlain))->sole();
        $second = AccountToken::where('token_hash', hash('sha256', $secondPlain))->sole();

        $this->assertNotNull($first->revoked_at);
        $this->assertFalse($first->isUsable());
        $this->assertTrue($second->isUsable());
        $this->assertSame(1, AccountToken::query()->usable()->count());

        $entries = AuditLog::orderBy('id')->get();
        $this->assertCount(2, $entries);
        $this->assertFalse($entries[0]->metadata['reissued']);
        $this->assertTrue($entries[1]->metadata['reissued']);
        $this->assertFalse($entries[1]->metadata['group_created']);
    }

    public function test_install_can_continue_when_the_only_secretary_is_inactive(): void
    {
        $this->groupWithSecretary(active: false);

        [$code, $output] = $this->install(['--email' => 'fresh@example.org']);

        $this->assertSame(0, $code);
        $this->assertSame(1, Group::count());
        $this->plainTokenFrom($output);
        $this->assertSame(1, AccountToken::query()->usable()->count());
    }

    public function test_install_refuses_an_email_that_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'taken@example.org']);

        [$code, $output] = $this->install(['--group' => 'G', '--email' => 'taken@example.org']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('already exists', $output);
        $this->assertSame(0, Group::count());
        $this->assertSame(0, AccountToken::count());
    }

    public function test_install_rejects_invalid_input_and_changes_nothing(): void
    {
        $cases = [
            ['--group' => 'G', '--email' => 'not-an-email'],
            ['--group' => 'G', '--email' => ''],
            ['--group' => '   ', '--email' => 'sec@example.org'],
            ['--group' => str_repeat('x', 121), '--email' => 'sec@example.org'],
        ];

        foreach ($cases as $options) {
            [$code] = $this->install($options);

            $this->assertSame(1, $code, 'Should have failed for: '.json_encode($options));
        }

        $this->assertSame(0, Group::count());
        $this->assertSame(0, AccountToken::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_install_refuses_when_more_than_one_group_exists(): void
    {
        Group::factory()->count(2)->create();

        [$code, $output] = $this->install(['--group' => 'G', '--email' => 'sec@example.org']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('More than one group', $output);
        $this->assertSame(0, AccountToken::count());
    }

    public function test_install_asks_for_missing_values_interactively(): void
    {
        $this->artisan('scoutdesk:install')
            ->expectsQuestion('Group name', 'Koura Scouts')
            ->expectsQuestion('Secretary email', 'first@example.org')
            ->expectsOutputToContain('/invitations/')
            ->assertExitCode(0);

        $this->assertSame('Koura Scouts', Group::sole()->name);
        $this->assertSame('first@example.org', AccountToken::sole()->email);
    }
}