<?php

namespace App\Modules\Audit\Actions;

use App\Modules\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * The single way to write an audit entry.
 *
 * Usage:
 *   app(RecordAudit::class)->handle('patrol.created', subject: $patrol, groupId: $group->id);
 *
 * Action keys are "<noun>.<verb>" in lowercase, e.g. "invitation.created".
 * $actorId defaults to the authenticated user (null for system or guest events).
 */
class RecordAudit
{
    /**
     * Metadata keys containing any of these fragments are refused.
     */
    private const FORBIDDEN_KEY_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        string $action,
        ?Model $subject = null,
        ?int $groupId = null,
        array $metadata = [],
        ?int $actorId = null,
    ): AuditLog {
        if (! preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $action) || strlen($action) > 64) {
            throw new InvalidArgumentException(
                "Audit action key [$action] must look like 'noun.verb' (lowercase, underscores, max 64 characters)."
            );
        }

        $this->assertMetadataIsSafe($metadata);

        $authenticatedId = Auth::id();

        return AuditLog::create([
            'actor_id' => $actorId ?? ($authenticatedId !== null ? (int) $authenticatedId : null),
            'group_id' => $groupId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $metadata
     */
    private function assertMetadataIsSafe(array $metadata): void
    {
        foreach ($metadata as $key => $value) {
            if (is_string($key)) {
                $lower = strtolower($key);

                foreach (self::FORBIDDEN_KEY_FRAGMENTS as $fragment) {
                    if (str_contains($lower, $fragment)) {
                        throw new InvalidArgumentException(
                            "Audit metadata key [$key] looks like a secret and must not be recorded."
                        );
                    }
                }
            }

            if (is_array($value)) {
                $this->assertMetadataIsSafe($value);
            }
        }
    }
}