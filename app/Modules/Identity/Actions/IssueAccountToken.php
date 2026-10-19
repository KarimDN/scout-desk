<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\IssuedAccountToken;
use App\Modules\Identity\Enums\AccountTokenPurpose;
use App\Modules\Identity\Models\AccountToken;
use Illuminate\Support\Str;

class IssueAccountToken
{
    public function handle(
        AccountTokenPurpose $purpose,
        string $email,
        ?string $role = null,
        ?int $groupId = null,
        ?int $patrolId = null,
        ?int $userId = null,
        ?int $createdBy = null,
    ): IssuedAccountToken {
        $plainToken = Str::random(48);

        $token = AccountToken::create([
            'purpose' => $purpose,
            'email' => Str::lower(trim($email)),
            'user_id' => $userId,
            'group_id' => $groupId,
            'patrol_id' => $patrolId,
            'role' => $role,
            'token_hash' => AccountToken::hashToken($plainToken),
            'expires_at' => now()->addHours((int) config('scoutdesk.invitation_lifetime_hours')),
            'created_by' => $createdBy,
        ]);

        return new IssuedAccountToken($token, $plainToken);
    }
}