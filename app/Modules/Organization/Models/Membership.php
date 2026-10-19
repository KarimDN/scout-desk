<?php

namespace App\Modules\Organization\Models;

use App\Modules\Organization\Enums\Role;
use App\Shared\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'user_id',
        'group_id',
        'patrol_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function patrol(): BelongsTo
    {
        return $this->belongsTo(Patrol::class);
    }

    public function scopeForGroup(Builder $query, Group|int $group): Builder
    {
        $groupId = $group instanceof Group ? $group->getKey() : $group;

        return $query->where('group_id', $groupId);
    }
    protected static function newFactory(): \Database\Factories\MembershipFactory
    {
        return \Database\Factories\MembershipFactory::new();
    }
}
