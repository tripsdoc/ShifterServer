<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trailer extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = "TR Info";
    protected $primaryKey = "TRNumber";
    protected $fillable = [
        'TRSize', 'Type', 'Location', 'TRPrefix', 'Status', 'DetailID', 'Team', 'TRTrailers', 'MaxWeight', 'CreatedBy', 'ModifiedBy', 'CreatedDt', 'ModifiedDt', 'DelStatus'
    ];
}
