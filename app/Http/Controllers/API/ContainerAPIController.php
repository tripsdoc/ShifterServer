<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContainerView;
use App\Models\ShifterUser;
use App\Models\TemporaryPark;
use App\Models\History;
use DB;
use Cache;

class ContainerAPIController extends Controller
{
    function debug() {
        $data = ContainerView::all();
        return response($data);
    }

    // -----------------------------------------  Park List Function -----------------------------------------------------------
    function getAll(Request $request) {
        $deliverto = ShifterUser::where('UserName', '=', $request->user)->pluck('Warehouse');
        $datawarehouse = array_map('trim', explode(",", $deliverto[0]));
        $result = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', '']);
        //$result->whereNotNull('Driver');
        $data = $result->paginate(20);

        $response['status'] = !$data->isEmpty();
        $response['current'] = $data->currentPage();
        $response['last'] = $data->lastPage();
        $response['data'] = $this->formatList($data->items());
        return response($response);
    }

    function getOverview($id) {
        $data = Cache::remember('CheckDummy' . $id, 60, function () {
            ContainerView::where('Dummy', '=', $id)->first();
        });

        $response['status'] = (!empty($data));
        $response['data'] = [$this->formatData($data)];
        return response($response);
    }

    function formatList($data) {
        $newdata = array();
        foreach($data as $key => $datas) {
            $loopdata = $this->formatData($datas);
            array_push($newdata, $loopdata);
        }
        return $newdata;
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
        $loopdata->IE = $datas["Import/Export"];
        //$loopdata->IE = $datas["I/E"];
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
        return $loopdata;
    }

    // --------------------------------------------------------------------------------------------------------------------------

    //Call function to request parking lots
    //or function to retrieve where container at
    function informShifter(Request $request) {
        $data = Cache::remember('CheckDummy' . $request->id, 60, function () {
            ContainerView::where('Dummy', '=', $request->id)->first();
        });
        $shifter = ShifterUser::where('warehouse', '=', $data->DeliverTo)->get();
        $ongoing = TemporaryPark::where('ShifterId', '=', $shifter->id)->get();

        //If shifter is still do the job ?
        
        //If shifter available
        //When inform show dialog to choice where to park in warehouse
        if($ongoing->isEmpty()) {
            $temp = new TemporaryPark();
            $temp->ShifterId = $shifter->id;
            $temp->CntrId = $data->Dummy;
            $temp->requestIn = date('Y-m-d H:i:s');
            $temp->createdBy = $request->user;
            $temp->status = $request->status; //1: Inform Shifter, 2: Request Position, 3: Return to Shifter, 4: Return to Driver

            $temp->save();

            //Call socket io events Container come to KD

        } else {
            
        }

        //Set container ETA (Estimated Time of Arrival)

    }

    //Function to send parking lots location to driver
    function shifterSetPark(Request $request) {
        $data = Cache::remember('CheckDummy' . $request->CntrId, 60, function () {
            ContainerView::where('Dummy', '=', $request->CntrId)->first();
        }); 

        $temp = TemporaryPark::find($request->tempId);

        //inform driver to park on selected parking lots
        $temp->parkId = $request->parkId;
        $temp->updatedBy = $request->user;
        $temp->status = $request->status; //1: Inform Shifter, 2: Request Position, 3: Return to Shifter, 4: Return to Driver

        $temp->save();
        //Call socket io events return parking lots data

    }

    function finishContainer(Request $request) {
        $temp = TemporaryPark::find($request->tempId);

        $history = new History();
        $driver = "Shifter : " . $request->shifter . ", Driver : " . $request->driver;
        $park = "Parking Lots : " . $request->lots . ", Warehouse : " . $request->warehouse;
        $history->Driver = $driver;
        $history->Park = $park;
        $history->CntrId = $temp->CntrId;
        $history->requestIn = $temp->requestIn;
        $history->finishTime = date('Y-m-d H:i:s'); //When container is in warehouse or went outside of KD

        $history->save();
    }
}
