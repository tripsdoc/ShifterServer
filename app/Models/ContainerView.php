<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContainerView extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = "OneeX";
    protected $primaryKey = "Dummy";
    public $timestamps = false;
}
