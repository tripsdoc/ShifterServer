<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContainerView extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = "vw_SHIFTER";
    protected $primaryKey = "Dummy";
    public $timestamps = false;
}
