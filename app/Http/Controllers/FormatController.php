<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FormatController extends Controller
{
    public static function formatOnee($datas, $hasPark = null) {
        $loopdata = new \stdClass();
        $loopdata->VesselID = $datas->VesselID;
        $loopdata->VesselName = $datas->VesselName;
        $loopdata->InVoy = $datas->Invoy;
        $loopdata->OutVoy = $datas->OutVoy;
        $loopdata->ETA = $datas->ETA;
        $loopdata->COD = $datas->COD;
        $loopdata->Berth = $datas->Berth;
        $loopdata->ETD = $datas->ETD;
        $loopdata->ServiceRoute = $datas->ServiceRoute;
        $loopdata->Client = $datas->Client;
        $loopdata->TruckTo = $datas->TruckTo;
        $loopdata->ImportExport = $datas->{'Import/Export'};
        $loopdata->LDPOD = $datas->{'LD/POD'};
        $loopdata->DeliverTo = $datas->DeliverTo;
        $loopdata->Prefix = $datas->Prefix;
        $loopdata->Number = $datas->Number;
        $loopdata->Seal = $datas->Seal;
        $loopdata->Size = $datas->Size;
        $loopdata->Type = $datas->Type;
        $loopdata->Remarks = $datas->Remarks;
        $loopdata->Status = $datas->Status;
        $loopdata->DateOfStuffUnStuff = $datas->{'DateofStuf/Unstuf'};
        $loopdata->Dummy = $datas->Dummy;
        //$loopdata->Expr1 = $datas->Expr1;
        //$loopdata->Expr2 = $datas->Expr2;
        //$loopdata->Expr3 = $datas->Expr3;
        //$loopdata->Chassis = $datas->Chassis;

        //$loopdata->TT = $datas->TT;
        //$loopdata->Pkg = $datas->TotalPkgs;
        //$loopdata->Yard = $datas->Yard;
        $loopdata->YardRemarks = $datas->YardRemarks;
        $loopdata->IE = $datas->{'Import/Export'};
        if (!empty($datas->ParkingLot)) {
            $loopdata->Park = self::setPark($datas);
            $loopdata->ParkingLot = $datas->ParkingLot;
        } else {
            if ($hasPark != null) {
                $checkStatus = "EMPTY/CREATED/STUFFED/SHIPPED/COMPLETED/CLOSED";
                if($datas->{'Import/Export'} == "Export") {
                    if(strpos($datas->YardRemarks, "RE-USE") && strpos($checkStatus, $datas->Status)) {
                        $loopdata->Park = self::setPark($hasPark);
                        $loopdata->ParkingLot = $hasPark->ParkingLot;
                    }
                } else {
                    $loopdata->Park = self::setPark($hasPark);
                    $loopdata->ParkingLot = $hasPark->ParkingLot;
                }
            }
        }
        //$loopdata->Driver = $datas->Driver;
        $loopdata->parkIn = (!empty($datas->ETA)) ? date('d/m H:i', strtotime($datas->ETA)) : "";
        $loopdata->parkOut = $datas->{'LD/POD'};
        return $loopdata;
    }

    static function setPark($datas) {
        $park = new \stdClass();
        $park->ParkID = $datas->ParkID;
        $park->Name = $datas->Name;
        $park->Type = $datas->ParkType;
        $park->Place = $datas->Place;
        $park->Detail = $datas->Detail;
        $park->created_at = $datas->ParkCreated;
        $park->updated_at = $datas->ParkUpdated;
        return $park;
    }
}
