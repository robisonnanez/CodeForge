<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'repository_id',
    'user_id',
    'title',
    'description',
    'status',
])]
class Issue extends Model
{
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
