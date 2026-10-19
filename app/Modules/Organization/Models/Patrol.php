<?php

namespace App\Modules\Organization\Models;

use App\Shared\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patrol extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'group_id',
        'name',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function scopeForGroup(Builder $query, Group|int $group): Builder
    {
        $groupId = $group instanceof Group ? $group->getKey() : $group;

        return $query->where('group_id', $groupId);
    }
    protected static function newFactory(): \Database\Factories\PatrolFactory
    {
        return \Database\Factories\PatrolFactory::new();
    }
}
