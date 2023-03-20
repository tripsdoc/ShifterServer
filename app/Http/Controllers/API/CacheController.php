<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContainerInfo;
use App\Models\ContainerView;
use App\Models\History;
use App\Models\Park;
use App\Models\TemporaryPark;
use App\Models\ShifterUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use DB;
use Storage;
use Cache;

class CacheController extends Controller
{
    function debug() {
        $DummyToAssign = '719886';
        $checkDummy = ContainerView::where('Dummy', '=', '719886')->first();
        if($checkDummy != null) {
            $newOnee = ContainerView::where('Prefix', '=', $checkDummy->Prefix)
            ->where('Number', '=', $checkDummy->Number)
            ->where('Import/Export', '=', 'Export')
            ->where('YardRemarks', 'like', '%RE-USE%')
            ->whereIn('Status', ['EMPTY', 'CREATED', 'STUFFED', 'SHIPPED', 'COMPLETED', 'CLOSED'])
            ->first();
            $DummyToAssign = (!empty($newOnee) && $newOnee != $dummy) ? $newOnee->Dummy : $dummy;
        }
        $response['status'] = TRUE;
        $response['message'] = $DummyToAssign;
        return response($response);
    }

    function debugUser() {
        $check = ShifterUser::all();
        dd($check);
    }

    function retrieveFile(Request $request) {
        //Assign File
        /* if($request->hasFile('assign')) {
            $file = $request->file('assign');
            if($file->isValid()) {
                $path = public_path() . '/uploads/images/store/';
                $file->move($path, $file->getClientOriginalName());
            }
        }
        //Remove File
        if($request->hasFile('remove')) {
            $file = $request->file('remove');
            if($file->isValid()) {
                $path = public_path() . '/uploads/images/store/';
                $file->move($path, $file->getClientOriginalName());
            }
        } */

        //Assign Text
        if($request->assign != "" && $request->assign != "[]") {
            $assignObject = json_decode($request->assign);
            foreach($assignObject as $key => $dataAssign) {
                if($dataAssign->dummy != 0) {
                    $returnText = $this->assignContainerToPark($dataAssign->dummy, $dataAssign->park, $dataAssign->username, null);
                } else {
                    $returnText = $this->assignContainerToPark($dataAssign->dummy, $dataAssign->park, $dataAssign->username, $dataAssign->trailer);
                }
                Storage::append('assign.log', $returnText);
            }
        }
        //Remove Text
        if($request->remove != "" && $request->remove != "[]") {
            $removeObject = json_decode($request->remove);
            foreach($removeObject as $key => $dataRemove) {
                $returnText = $this->removeContainer($dataRemove->park, $dataRemove->username);
                
                Storage::append('remove.log', $returnText);
            }
        }
        
        $response['status'] = TRUE;
        $response['message'] = "Complete Process";
        return response($response);
    }

    function removeOldDummyFromOngoing($dummy) {
        $data = Cache::remember('CheckDummy' . $dummy, 60, function () use ($dummy) {
            ContainerView::where('Dummy', '=', $dummy)->first();
        });
        if($data != null) {
            $check = Cache::remember('CheckContainer' . $data->Prefix . '-' . $data->Number, 60, function () use ($data) {
                ContainerView::where('Prefix', '=', $data->Prefix)->where('Number', '=', $data->Number)->get();
            });
            foreach($check as $key => $datas) {
                $deletedata = TemporaryPark::where('Dummy', '=', $datas->Dummy)->delete();
            }
        } else {
            $deletedata = TemporaryPark::where('Dummy', '=', $dummy)->delete();
        }
        return;
    }

    function checkReUSE($dummy) {
        $DummyToAssign = $dummy;
        $checkDummy = Cache::remember('CheckDummy' . $dummy, 60, function () use ($dummy) {
            ContainerView::where('Dummy', '=', $dummy)->first();
        });
        if($checkDummy != null) {
            $newOnee = Cache::remember('NewOnee' . $checkDummy->Prefix . '-' . $checkDummy->Number, 60, function () use ($checkDummy) {
                ContainerView::where('Prefix', '=', $checkDummy->Prefix)
                ->where('Number', '=', $checkDummy->Number)
                ->where('Import/Export', '=', 'Export')
                ->where('YardRemarks', 'like', '%RE-USE%')
                ->whereIn('Status', ['EMPTY', 'CREATED', 'STUFFED', 'SHIPPED', 'COMPLETED', 'CLOSED'])
                ->first();
            });
            $DummyToAssign = (!empty($newOnee) && $newOnee != $dummy) ? $newOnee->Dummy : $dummy;
        }
        return $DummyToAssign;
    }

    function assignContainerToPark($dummy, $park, $user, $trailer) {
        $textToReturn = date('Y-m-d H:i:s') . ", dummy: " . $dummy . ", park: " . $park . ", user: " . $user;
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $park)->first();
        if($dummy != 0) {
            $DummyToAssign = $this->checkReUSE($dummy);
            $this->removeOldDummyFromOngoing($DummyToAssign);
        } else {
            $DummyToAssign = 0;
        }
        if(empty($check)) {
            $temp = new TemporaryPark();

            $temp->ParkingLot = $park;
            $temp->Dummy = $DummyToAssign;
            $temp->trailer = $trailer;
            $temp->createdBy = $user;
            $temp->updatedBy = $user;
            $temp->updatedDt = date('Y-m-d H:i:s');
            if($temp->save()) {
                $response['status'] = TRUE;
                $response['data'] = $temp;
                $dataRedis = "1," . $park . "," .  $temp->Dummy;
                $textToReturn = $textToReturn . " Success";
                $this->broadcastRedis($dataRedis);
            }
        } else {
            if($check->Dummy != $dummy) {
                $history = new History();
                $history->SetDt = $check->updatedDt;
                $history->UnSetDt = date('Y-m-d H:i:s');
                $history->ParkingLot = $check->ParkingLot;
                $history->Dummy = $check->Dummy;
                $history->trailer = $check->trailer;
                $history->createdBy = $user;
    
                if($history->save()){
                    $check->Dummy = $DummyToAssign;
                    $check->trailer = $trailer;
                    $check->updatedBy = $user;
                    $check->updatedDt = date('Y-m-d H:i:s');
    
                    $check->save();
                    $response['status'] = TRUE;
                    $response['data'] = $check;
                    $dataRedis = "1," . $park . "," .  $check->Dummy;
                    $textToReturn = $textToReturn . " Success";
                    $this->broadcastRedis($dataRedis);
                }
            } else {
                $textToReturn = $textToReturn . " Duplicate";
            }
        }
        return $textToReturn;
    }

    function removeContainer($park, $user) {
        $textToReturn = date('Y-m-d H:i:s') . ", park: " . $park . ", user: " . $user;
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $park)->first();
        if(!empty($check)) {
            $history = new History();
            $history->SetDt = $check->updatedDt;
            $history->UnSetDt = date('Y-m-d H:i:s');
            $history->ParkingLot = $check->ParkingLot;
            $history->Dummy = $check->Dummy;
            $history->trailer = $check->trailer;
            $history->createdBy = $user;
    
            if($history->save()){
                $check->delete();
                $response['status'] = TRUE;
                $response['data'] = $history;
                $dataRedis = "0," . $park . ",0";
                $textToReturn = $textToReturn . " Success";
                $this->broadcastRedis($dataRedis);
            }
        } else {
            $textToReturn = $textToReturn . " Empty/Already Deleted";
        }
        
        return $textToReturn;
    }

    function broadcastRedis($data) {
        $redis = Redis::connection();
        $redis->publish("update-park", $data);
        return;
    }
}
