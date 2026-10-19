<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\AccountTokenPurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A single-use, expiring link token (invitation or password reset).
 * Only the SHA-256 hash of the token is stored. Identity does not import
 * Organization classes: 'role' is a plain string, group/patrol are plain IDs.
 */
class AccountToken extends Model
{
    protected $fillable = [
        'purpose',
        'email',
        'user_id',
        'group_id',
        'patrol_id',
        'role',
        'token_hash',
        'expires_at',
        'created_by',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'purpose' => AccountTokenPurpose::class,
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Not used, not revoked and not expired.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}