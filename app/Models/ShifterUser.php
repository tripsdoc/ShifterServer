<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShifterUser extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = 'SHIFTER_ShifterUser';
    protected $primaryKey = 'ShifterID';
    protected $fillable = [
        'Name', 'UserName', 'Warehouse', 'Password'
    ];
}
