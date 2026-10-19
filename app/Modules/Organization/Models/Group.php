<?php

namespace App\Modules\Organization\Models;

use App\Shared\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'name',
    ];

    public function patrols(): HasMany
    {
        return $this->hasMany(Patrol::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function scopeForGroup(Builder $query, self|int $group): Builder
    {
        $groupId = $group instanceof self ? $group->getKey() : $group;

        return $query->whereKey($groupId);
    }

    protected static function newFactory(): \Database\Factories\GroupFactory
    {
        return \Database\Factories\GroupFactory::new();
    }
}
