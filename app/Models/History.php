<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = "HSC_ParkHistory";
    protected $primaryKey = "HistoryID";
    public $timestamps = false;
    protected $fillable = [
        'SetDt', 'UnSetDt', 'ParkingLot', 'Dummy', 'createdBy', 'createdDt', 'updatedBy', 'updatedDt'
    ];
}
