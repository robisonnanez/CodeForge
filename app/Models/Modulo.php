<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    protected $table = 'modulos';

    protected $primaryKey = 'idModulos';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'idModulos',
        'nmodulo',
        'orden',
        'icono',
        'color',
        'detalle',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'idModulos', 'idModulos');
    }
}