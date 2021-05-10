<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContainerInfo extends Model
{
    protected $connection = 'sqlsrv2';
    //DeliverTo column will be used when asign task to the shifter
    protected $table = "ContainerInfo";
    protected $primaryKey = "Dummy";
    public $timestamps = false;
    protected $fillable = [
        'JobNumber', 'ContainerPrefix', 'ContainerNumber', 'SealNumber',   // (int(PK), int, varchar(6), varchar(10), varchar(20))
        'ContainerType', 'Tri-Axle', 'Direct', 'BkRef', 'OpCode',                   // (varchar(10), bit, bit, varchar(50), varchar(2))
        'Yard', 'Status', 'DateofStuf/Unstuf', 'LastDay', 'Remarks',                // (varchar(50), varchar(50), datetime, datetime, varchar(255))
        'RemarksWhse', 'TT', 'EstWt', 'Wt', 'EmptyDate',                            // (varchar(100), varchar(50), int, bit, datetime)
        'DCON', 'Floorboard', 'TallyBy', 'DO', 'EIR',                               // (bit, varchar(255), varchar(50), bit, bit)
        'PSABillDate', 'Ref', 'DCONLink', 'DeliverTo', 'ESN',                       // (varchar(6), varchar(20), varchar(50), varchar(10), bit)
        'SendCntrNo', 'Permit', 'Class', 'OBL', 'F5',                               // (bit, varchar(50), varchar(50), varchar(20), bit)
        'TotalVolume', 'F5UnstuffDate', 'F5LastDay', 'J5Slot', 'YardRemarks',       // (float, datetime, datetime, datetime, varchar(50))
        'StickerNo', 'DamageAmt', 'DamageAmtAbsorb', 'Bay', 'Stevedore',            // (int, money, money, varchar(20), varchar(50))
        'SeventPoints', 'StartTime', 'EndTime', 'IsOK', 'Agent',                    // (varchar(300), datetime, datetime, bit, nvarchar(255))
        'ShutOut', 'ContainerSize', 'PSABillNumber'                                 // (varchar(255), tinyint, varchar(6))
        
        //'containerNumber', 'clientId', 'size', 'note', 'created_at', 'created_by'
    ];
}
