<?php

namespace App\Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One immutable audit entry. Entries are written only through
 * App\Modules\Audit\Actions\RecordAudit and are never updated or deleted.
 *
 * Do not call AuditLog::query()->update() or ->delete(): query-builder
 * writes bypass the model events that enforce this.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'group_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Audit log entries are append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new LogicException('Audit log entries are append-only and cannot be deleted.');
        });
    }
}