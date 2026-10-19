<?php

namespace Tests\Feature\Identity;

use App\Modules\Identity\Actions\IssueAccountToken;
use App\Modules\Identity\Data\IssuedAccountToken;
use App\Modules\Identity\Enums\AccountTokenPurpose;
use App\Modules\Identity\Models\AccountToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountTokenTest extends TestCase
{
    use RefreshDatabase;

    private function issue(string $email = 'someone@example.org'): IssuedAccountToken
    {
        return app(IssueAccountToken::class)->handle(
            AccountTokenPurpose::Invitation,
            $email,
            role: 'group_secretary',
        );
    }

    public function test_only_the_hash_of_the_token_is_stored(): void
    {
        $issued = $this->issue();
        $plain = $issued->plainToken;

        $this->assertSame(48, strlen($plain));
        $this->assertSame(hash('sha256', $plain), $issued->token->token_hash);

        $this->assertDatabaseHas('account_tokens', ['token_hash' => hash('sha256', $plain)]);
        $this->assertDatabaseMissing('account_tokens', ['token_hash' => $plain]);

        $row = DB::table('account_tokens')->first();
        $this->assertStringNotContainsString($plain, json_encode($row));
    }

    public function test_token_expires_after_the_configured_lifetime(): void
    {
        $this->freezeTime();

        $token = $this->issue()->token->fresh();

        $this->assertSame(
            now()->addHours(72)->format('Y-m-d H:i:s'),
            $token->expires_at->format('Y-m-d H:i:s')
        );
    }

    public function test_email_is_trimmed_and_lowercased(): void
    {
        $token = $this->issue('  Sec.Retary@Example.ORG ')->token;

        $this->assertSame('sec.retary@example.org', $token->fresh()->email);
    }

    public function test_every_token_is_unique(): void
    {
        $a = $this->issue();
        $b = $this->issue();

        $this->assertNotSame($a->plainToken, $b->plainToken);
        $this->assertNotSame($a->token->token_hash, $b->token->token_hash);
    }

    public function test_usable_scope_excludes_used_revoked_and_expired_tokens(): void
    {
        $usable = $this->issue('a@example.org')->token;

        $used = $this->issue('b@example.org')->token;
        $used->forceFill(['used_at' => now()])->save();

        $revoked = $this->issue('c@example.org')->token;
        $revoked->revoke();

        $expired = $this->issue('d@example.org')->token;
        $expired->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->assertSame([$usable->id], AccountToken::query()->usable()->pluck('id')->all());

        $this->assertTrue($usable->fresh()->isUsable());
        $this->assertFalse($used->fresh()->isUsable());
        $this->assertFalse($revoked->fresh()->isUsable());
        $this->assertFalse($expired->fresh()->isUsable());
    }

    public function test_token_hash_is_hidden_from_serialization(): void
    {
        $token = $this->issue()->token;

        $this->assertArrayNotHasKey('token_hash', $token->toArray());
    }
}