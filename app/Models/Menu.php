<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = [
        'idModulos',
        'nombre',
        'url',
        'icono',
        'id_menu',
        'main',
        'orden',
        'cesdo',
        'permission_name',
    ];

    protected function casts(): array
    {
        return [
            'main' => 'boolean',
            'cesdo' => 'boolean',
        ];
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'idModulos', 'idModulos');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_menu');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'id_menu')->orderBy('orden');
    }
}
