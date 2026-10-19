<?php

namespace Tests\Feature\Audit;

use App\Modules\Audit\Actions\RecordAudit;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function record(string $action = 'group.created', array $extra = []): AuditLog
    {
        return app(RecordAudit::class)->handle($action, ...$extra);
    }

    public function test_entry_records_actor_group_subject_and_metadata(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $entry = $this->record('group.created', [
            'subject' => $group,
            'groupId' => $group->id,
            'metadata' => ['name' => $group->name, 'nested' => ['role' => 'group_secretary']],
            'actorId' => $user->id,
        ]);

        $entry = $entry->fresh();

        $this->assertSame($user->id, $entry->actor_id);
        $this->assertSame($group->id, $entry->group_id);
        $this->assertSame('group.created', $entry->action);
        $this->assertSame($group->getMorphClass(), $entry->subject_type);
        $this->assertSame($group->id, $entry->subject_id);
        $this->assertSame(['name' => $group->name, 'nested' => ['role' => 'group_secretary']], $entry->metadata);
        $this->assertNotNull($entry->created_at);
    }

    public function test_entry_records_the_request_ip(): void
    {
        $this->app->instance(
            'request',
            Request::create('/x', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9'])
        );

        $this->assertSame('203.0.113.9', $this->record()->fresh()->ip);
    }

    public function test_actor_defaults_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertSame($user->id, $this->record()->fresh()->actor_id);
    }

    public function test_actor_is_null_when_nobody_is_logged_in(): void
    {
        $this->assertNull($this->record()->fresh()->actor_id);
    }

    public function test_entries_cannot_be_updated(): void
    {
        $entry = $this->record();

        $entry->action = 'group.changed';

        $this->expectException(LogicException::class);
        $entry->save();
    }

    public function test_entries_cannot_be_deleted(): void
    {
        $entry = $this->record();

        try {
            $entry->delete();
            $this->fail('Deleting an audit entry should have thrown.');
        } catch (LogicException) {
            // expected
        }

        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_metadata_that_looks_like_a_secret_is_refused(): void
    {
        $forbidden = [
            ['password' => 'x'],
            ['Password_Confirmation' => 'x'],
            ['api_token' => 'x'],
            ['remember_token' => 'x'],
            ['token_hash' => 'x'],
            ['client_secret' => 'x'],
            ['Authorization' => 'Bearer x'],
            ['user' => ['password' => 'x']],
            ['a' => ['b' => ['cookie' => 'x']]],
        ];

        foreach ($forbidden as $metadata) {
            try {
                $this->record('setup.test_run', ['metadata' => $metadata]);
                $this->fail('Metadata should have been refused: '.json_encode(array_keys($metadata)));
            } catch (InvalidArgumentException) {
                // expected
            }
        }

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_invalid_action_keys_are_refused(): void
    {
        $invalid = [
            '',
            'created',
            'Patrol.created',
            'patrol.',
            '.created',
            'patrol created',
            'patrol.created.extra',
            'patrol-created.now',
            str_repeat('a', 40).'.'.str_repeat('b', 40),
        ];

        foreach ($invalid as $key) {
            try {
                $this->record($key);
                $this->fail("Action key should have been refused: [$key]");
            } catch (InvalidArgumentException) {
                // expected
            }
        }

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_valid_action_keys_are_accepted(): void
    {
        foreach (['invitation.created', 'setup.install_run', 'login.failed', 'patrol.deactivated'] as $key) {
            $this->assertSame($key, $this->record($key)->action);
        }

        $this->assertDatabaseCount('audit_logs', 4);
    }

    public function test_table_has_no_updated_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('audit_logs', 'created_at'));
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));
    }
}