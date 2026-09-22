<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedUser extends Model
{
    protected $table = 'deleted_users';

    protected $fillable = [
        'client_id',
        'reason',
        'name',
        'email',
        'phone',
        'deleted_at',
    ];
}
