<?php

namespace App\Models;

use App\Traits\Loggable;
use App\Traits\Scope\RoleScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory, RoleScope, Loggable;
}
