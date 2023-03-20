<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Park extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = "SHIFTER_Park";
    protected $primaryKey = "ParkID";
    protected $fillable = [
        'Name', 'Detail', 'Type', 'Place'
    ];
}
