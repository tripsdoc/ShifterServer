<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FormatController;
use App\Models\ContainerInfo;
use App\Models\ContainerView;
use App\Models\History;
use App\Models\Park;
use App\Models\TemporaryPark;
use App\Models\Trailer;
use Carbon\Carbon;
use DataTables;
use Date;
use DB;
use View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Cache;

class ParkController extends Controller
{
    function debug() {
        $data = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', ''])
        ->paginate(20);
        return response($data);
    }

    function getDummy() {
        $parkid = $_GET['park'];
        $data = TemporaryPark::where('ParkingLot', '=', $parkid)->first();
        return (!empty($data)) ? $data->Dummy : null;
    }

    // -----------------------------------------  Picker Function -----------------------------------------------------------

    function getLikeContainer(Request $request) {
        $result = DB::table('HSC2012.dbo.Onee AS IP')
            ->join('HSC2012.dbo.HSC_OngoingPark AS OP', 'IP.Dummy', '=', 'OP.Dummy', 'full outer')
            ->join('HSC2012.dbo.HSC_Park AS P', 'OP.ParkingLot', '=', 'P.ParkID', 'full outer')
            ->whereNotNull('Status')
            ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', ''])
            ->where('Number', 'like', '%' . $request->number . '%')
            ->select('IP.*', 'OP.ParkingLot', 'P.Type as ParkType', 'P.created_at as ParkCreated', 'P.updated_at as ParkUpdated', 'P.*')
            ->orderBy('IP.ETA')
            ->orderBy('IP.Client')
            ->orderBy('IP.Prefix')
            ->orderBy('IP.Number')
            ->orderBy('OP.createdDt', 'desc')
            ->get();
        $dataArray = array();
        $hasParkingLot = array();
        $duplicate = array();
        foreach($result as $key => $datas) {
            if (!empty($datas->ParkingLot)) {
                $foo = (array) $datas;
                $foo["Checked"] = $datas->Prefix . $datas->Number;
                $foo = (object) $foo;
                array_push($hasParkingLot, $foo);
            }
            $columnShow = array_column($hasParkingLot, 'Checked');
            $check = $datas->Prefix . $datas->Number;
            $index = array_search($check, $columnShow);

            if ($index !== false) {
                $searchData = $hasParkingLot[$index];
                $newdata = FormatController::formatOnee($datas, $searchData);
            } else {
                $newdata = FormatController::formatOnee($datas);
            }
            array_push($dataArray, $newdata);
        }
        $response['status'] = !empty($dataArray);
        $response['data'] = $dataArray;
        return response($response);
    }

    function getLikeTrailer(Request $request) {
        $data = Cache::remember('LikeTrailer' . $request->trailer, 60, function () {
            Trailer::where('DelStatus', '=', 'N')
            ->where('TRTrailers', 'like', '%' . $request->trailer . '%')->get();
        });
        $response['status'] = !$data->isEmpty();
        $response['data'] = $data;
        return response($response);
    }

    // -----------------------------------------  Park List Function -----------------------------------------------------------
    function getParkJson(Request $request) {
        $data = Park::all();
        $dataUser = $request->user;
        $dataArray = $this->convertData($data, $dataUser);
        $type = Park::groupBy('Type')->pluck('Type');
        $dataPlace = array();
        foreach ($type as $key => $datas) {
            $park = Park::where('Type', '=', $datas)->groupBy('place')->pluck('place');
            array_push($dataPlace, $park);
        }

        $response['status'] = !$data->isEmpty();
        $response['place'] = $dataPlace;
        $response['data'] = $dataArray;
        return response($response);
    }

    function getTrailerJson(Request $request) {
        $data = Trailer::all();
        $response['status'] = !$data->isEmpty();
        $response['data'] =  $data;
        return response($response);
    }

    function getContainerJson() {
        /* $data = Cache::remember('ContainerJSON', 60, function () {
            return ContainerView::
            whereNotNull('Status')
            ->whereNotIn('Status', ['NEW', 'NOMINATED', 'PROCESSED', ''])
            ->get();
        }); */
        $result = DB::table('HSC2012.dbo.Onee AS IP')
            ->join('HSC2012.dbo.HSC_OngoingPark AS OP', 'IP.Dummy', '=', 'OP.Dummy', 'full outer')
            ->join('HSC2012.dbo.HSC_Park AS P', 'OP.ParkingLot', '=', 'P.ParkID', 'full outer')
            ->whereNotNull('Status')
            ->whereNotIn('Status', ['NEW', 'NOMINATED', 'PROCESSED', ''])
            ->limit(1000)
            ->select('IP.*', 'OP.ParkingLot', 'P.Type as ParkType', 'P.created_at as ParkCreated', 'P.updated_at as ParkUpdated', 'P.*')
            ->orderBy('IP.ETA')
            ->orderBy('IP.Client')
            ->orderBy('IP.Prefix')
            ->orderBy('IP.Number')
            ->orderBy('OP.createdDt', 'desc')
            ->get();
        $dataArray = array();
        $hasParkingLot = array();
        $duplicate = array();
        foreach($result as $key => $datas) {
            if (!empty($datas->ParkingLot)) {
                $foo = (array) $datas;
                $foo["Checked"] = $datas->Prefix . $datas->Number;
                $foo = (object) $foo;
                array_push($hasParkingLot, $foo);
            }
            $columnShow = array_column($hasParkingLot, 'Checked');
            $check = $datas->Prefix . $datas->Number;
            $index = array_search($check, $columnShow);

            if ($index !== false) {
                $searchData = $hasParkingLot[$index];
                $newdata = FormatController::formatOnee($datas, $searchData);
            } else {
                $newdata = FormatController::formatOnee($datas);
            }
            array_push($dataArray, $newdata);
        }
        /* $dataArray = array();
        foreach($data as $key => $datas) {
            $newdata = FormatCOntroller::formatOnee($datas);
            array_push($dataArray, $newdata);
        } */
        $response['status'] = !empty($dataArray);
        $response['data'] = $dataArray;
        return response($response);
    }

    // -------------------------------------------------------------------------------------------------------------------------
    
    function removeOldDummyFromOngoing($dummy) {
        $data = Cache::remember('CheckDummy' . $dummy, 60, function () {
            return ContainerView::where('Dummy', '=', $dummy)->first();
        });
        $check = Cache::remember('CheckContainer' . $data->Prefix . '-' . $data->Number, 60, function () {
            return ContainerView::where('Prefix', '=', $data->Prefix)->where('Number', '=', $data->Number)->get();
        });
        foreach($check as $key => $datas) {
            $deletedata = TemporaryPark::where('Dummy', '=', $datas->Dummy)->delete();
        }
    }

    function assignContainerToPark(Request $request) {
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $request->park)->first();
        if($request->trailer != null) {
            $checkTrailer = TemporaryPark::where('trailer', '=', $request->trailer)->delete();
        }
        if($request->dummy != 0) {
            $DummyToAssign = $this->checkReUSE($request->dummy);
            $this->removeOldDummyFromOngoing($DummyToAssign);
        } else {
            $DummyToAssign = 0;
        }
        if(empty($check)) {
            $temp = new TemporaryPark();

            $temp->ParkingLot = $request->park;
            $temp->Dummy = $DummyToAssign;
            $temp->createdBy = $request->user;
            $temp->updatedDt = date('Y-m-d H:i:s');
            $temp->updatedBy = $request->user;
            $temp->trailer = $request->trailer;
            if($temp->save()) {
                $response['status'] = TRUE;
                $response['data'] = $temp;
                $dataRedis = "1," . $request->park . "," .  $temp->Dummy;
                $this->broadcastRedis($dataRedis);
                return response($response);
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Server error, cannot asign data!";
                return response($response);
            }
        } else {
            $history = new History();
            $history->SetDt = $check->updatedDt;
            $history->UnSetDt = date('Y-m-d H:i:s');
            $history->ParkingLot = $check->ParkingLot;
            $history->Dummy = $check->Dummy;
            $history->trailer = $check->trailer;
            $history->createdBy = $request->user;

            if($history->save()){
                $check->Dummy = $DummyToAssign;
                $check->trailer = $request->trailer;
                $check->updatedBy = $request->user;
                $check->updatedDt = date('Y-m-d H:i:s');

                $check->save();
                $response['status'] = TRUE;
                $response['data'] = $check;
                $dataRedis = "1," . $request->park . "," .  $check->Dummy;
                $this->broadcastRedis($dataRedis);
                return response($response);
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Server error, cannot asign data!";
                return response($response);
            }
        }
    }

    function changePark(Request $request) {
        date_default_timezone_set('Asia/Singapore');
        $DummyToAssign = $this->checkReUSE($request->dummy);
        $DummyOngoing = $this->getOngoingDummy($request->dummy);
        $oldpark = TemporaryPark::where('Dummy', '=', $DummyOngoing)->first();
        $isParkAssign = TemporaryPark::where('ParkingLot', '=', $request->park)->first();
        if (!empty($oldpark)) {
            $oldlot = $oldpark->ParkingLot;
            $oldpark->delete();
            if (!empty($isParkAssign)) {
                $isParkAssign->delete();
                $history = new History();
                $history->SetDt = $isParkAssign->updatedDt;
                $history->UnSetDt = date('Y-m-d H:i:s');
                $history->ParkingLot = $isParkAssign->ParkingLot;
                $history->Dummy = $isParkAssign->Dummy;
                $history->trailer = $isParkAssign->trailer;
                $history->createdBy = $request->user;
                $history->save();
            }
            $newpark = new TemporaryPark();
            $newpark->ParkingLot = $request->park;
            $newpark->Dummy = $DummyToAssign;
            $newpark->createdBy = $request->user;
            $newpark->updatedDt = date('Y-m-d H:i:s');
            $newpark->updatedBy = $request->user;
            if($newpark->save()) {
                $response['status'] = TRUE;
                $response['data'] = $newpark;
                $dataOld = "0," . $oldlot . ",0";
                $dataRedis = "1," . $request->park . "," .  $newpark->Dummy;
                $this->broadcastRedis($dataRedis);
                $this->broadcastRedis($dataOld);
                return response($response);
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Server error, cannot asign data!";
                return response($response);
            }
        }
    }

    function removeContainer(Request $request) {
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $request->park)->first();

        $history = new History();
        $history->SetDt = $check->updatedDt;
        $history->UnSetDt = date('Y-m-d H:i:s');
        $history->ParkingLot = $check->ParkingLot;
        $history->Dummy = $check->Dummy;
        $history->trailer = $check->trailer;
        $history->createdBy = $request->user;

        if($history->save()){
            $check->delete();
            $response['status'] = TRUE;
            $response['data'] = $history;
            $dataRedis = "0," . $request->park . ",0";
            $this->broadcastRedis($dataRedis);
            return response($response);
        } else {
            $response['status'] = FALSE;
            $response['errMsg'] = "Server error, cannot asign data!";
            return response($response);
        }
    }

    function convertData($data, $dataUser) {
        $fulldate = date("Y-m-d H:i:s");
        $dataArray = array();
        foreach ($data as $key => $datas) {
            //Get ongoing park
            $temppark = TemporaryPark::where('ParkingLot', $datas->ParkID)
            ->get();
            $datatemparray = array();
            if(!$temppark->isEmpty()) {
                foreach($temppark as $key => $temp) {
                    if ($temp->Dummy != 0) {
                        $container = ContainerView::where('Dummy', '=', $temp->Dummy)->first();
                        $ndt = new \stdClass();
                        $ndt->ParkingLot = $temp->ParkingLot;
                        $ndt->Dummy = $temp->Dummy;
                        $ndt->createdBy = $temp->createdBy;
                        $ndt->createdDt = $temp->createdDt;
                        $ndt->updatedBy = $temp->updatedBy;
                        $ndt->updatedDt = $temp->updatedDt;
                        if(!empty($container)) {
                            $ndt->container = $this->formatData($container);
                        }
        
                        array_push($datatemparray, $ndt);
                    }
                }
            }

            $loopData = array(
                "id" => $datas->ParkID,
                "name" => $datas->Name,
                "place" => $datas->Place,
                "type" => $datas->Type,
                "availability" => ($temppark->isEmpty())? 1 : 0,
                "temp" => $datatemparray,
                "trailer" => (!$temppark->isEmpty())? $temppark[0]->trailer : null,
                "textUpdated" => (!$temppark->isEmpty())? "Updated by " . $temppark[0]->updatedBy . " on " . date('d/m H:i', strtotime($temppark[0]->updatedDt)) : null
            );
            array_push($dataArray, $loopData);
        }

        return $dataArray;
    }

    function formatData($datas) {
        $loopdata = new \stdClass();
        $loopdata->VesselID = $datas->VesselID;
        $loopdata->VesselName = $datas->VesselName;
        $loopdata->InVoy = $datas->InVoy;
        $loopdata->OutVoy = $datas->OutVoy;
        $loopdata->ETA = $datas->ETA;
        $loopdata->COD = $datas->COD;
        $loopdata->Berth = $datas->Berth;
        $loopdata->ETD = $datas->ETD;
        $loopdata->ServiceRoute = $datas->ServiceRoute;
        $loopdata->Client = $datas->Client;
        $loopdata->TruckTo = $datas->TruckTo;
        $loopdata->ImportExport = $datas["Import/Export"];
        $loopdata->IE = $datas["I/E"];
        $loopdata->LDPOD = $datas["LD/POD"];
        $loopdata->DeliverTo = $datas->DeliverTo;
        $loopdata->Prefix = $datas->Prefix;
        $loopdata->Number = $datas->Number;
        $loopdata->Seal = $datas->Seal;
        $loopdata->Size = $datas->Size;
        $loopdata->Type = $datas->Type;
        $loopdata->Remarks = $datas->Remarks;
        $loopdata->Status = $datas->Status;
        $loopdata->DateOfStuffUnStuff = $datas["DateofStuf/Unstuf"];
        $loopdata->Dummy = $datas->Dummy;
        $loopdata->Expr1 = $datas->Expr1;
        $loopdata->Expr2 = $datas->Expr2;
        $loopdata->Expr3 = $datas->Expr3;
        $loopdata->Chassis = $datas->Chassis;
        $loopdata->Driver = $datas->Driver;
        $loopdata->YardRemarks = $datas->YardRemarks;
        return $loopdata;
    }

    function checkReUSE($dummy) {
        $checkDummy = Cache::remember('CheckDummy' . $dummy, 60, function () {
            return ContainerView::where('Dummy', '=', $dummy)->first();
        });
        $newOnee = Cache::remember('NewOnee' . $checkDummy->Prefix . '-' . $checkDummy->Number, 60, function () {
            ContainerView::where('Prefix', '=', $checkDummy->Prefix)
            ->where('Number', '=', $checkDummy->Number)
            ->where('Import/Export', '=', 'Export')
            ->where('YardRemarks', 'like', '%RE-USE%')
            ->whereIn('Status', ['EMPTY', 'CREATED', 'STUFFED', 'SHIPPED', 'COMPLETED', 'CLOSED'])
            ->first();
        });
        $DummyToAssign = (!empty($newOnee) && $newOnee != $dummy) ? $newOnee->Dummy : $dummy;
        return $DummyToAssign;
    }

    function getOngoingDummy($dummy) {
        $reqdummy = Cache::remember('CheckDummy' . $dummy, 60, function () {
            return ContainerView::where('Dummy', '=', $dummy)->first();
        }); 
        $data = DB::table('HSC2012.dbo.Onee AS IP')
        ->join('HSC2012.dbo.HSC_OngoingPark AS IB', 'IP.Dummy', '=', 'IB.Dummy')
        ->where('Prefix', '=', $reqdummy->Prefix)
        ->where('Number', '=', $reqdummy->Number)
        ->first();
        return $data->Dummy;
    }

    function broadcastRedis($data) {
        $redis = Redis::connection();
        $redis->publish("update-park", $data);
        return;
    }
}
