<?php

namespace App\Models;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Permission extends \Spatie\Permission\Models\Permission
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('rbac')
            ->logOnly(['name', 'guard_name'])
            ->logOnlyDirty();
    }
}
