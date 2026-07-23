<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'description',
    'visibility',
    'storage_uuid',
    'state',
    'default_branch',
])]
class Repository extends Model
{
    use SoftDeletes;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(RepositoryMember::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }
}
