<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FormatController;
use Illuminate\Http\Request;
use App\Models\History;
use App\Models\TemporaryPark;
use App\Models\ContainerView;
use App\Models\ContainerInfo;
use App\Models\Park;
use App\Models\ShifterUser;
use DB;
use Cache;

class HistoryController extends Controller
{
    function checkDummyisExist($dummy) {
        $data = ContainerView::where('Dummy', '=', $dummy)->get();
        $response['isExist'] = !$data->isEmpty();
        $response['data'] = $data;
        return response($response);
    }

    function debuggetSummaryJson() {  
        $result = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', '']);
        $result->orderBy('ETA')
        ->orderBy('Client')
        ->orderBy('Prefix')
        ->orderBy('Number');
        $data = $result->get();
        $dataArray = array();
        foreach($data as $key => $datas) {
            $newdata = $this->formatContainer($datas);
            array_push($dataArray, $newdata);
        }
        $response['count'] = count($dataArray);
        $response['status'] = !$data->isEmpty();
        $response['data'] = $dataArray;
        return response($response);
    }

    function getSummaryJson() {
        $result = Cache::remember('SummaryJSON', 30, function () {
            return DB::table('HSC2017.dbo.vw_SHIFTER AS IP')
            ->join('HSC2017.dbo.SHIFTER_OngoingPark AS OP', 'IP.Dummy', '=', 'OP.Dummy', 'full outer')
            ->join('HSC2017.dbo.SHIFTER_Park AS P', 'OP.ParkingLot', '=', 'P.ParkID', 'full outer')
            ->whereNotNull('Status')
            ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', ''])
            ->select('IP.*', 'OP.ParkingLot', 'P.Type as ParkType', 'P.created_at as ParkCreated', 'P.updated_at as ParkUpdated', 'P.*')
            ->orderBy('IP.ETA')
            ->orderBy('IP.Client')
            ->orderBy('IP.Prefix')
            ->orderBy('IP.Number')
            ->orderBy('OP.createdDt', 'desc')
            ->get();
        });
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
        
        if (isset($_GET["filter"]) != null) {
            $dataArray = array_filter($dataArray, function ($item) {
                return $item->ImportExport === $_GET["filter"];
            });
        }
        $response['count'] = count($dataArray);
        $response['status'] = !empty($dataArray);
        $response['data'] = $dataArray;
        return response($response);
    }

    function getParkingLot($prefix, $number) {
        $result = DB::table('HSC2017.dbo.vw_SHIFTER AS IP')
        ->join('HSC2017.dbo.SHIFTER_OngoingPark AS IB', 'IP.Dummy', '=', 'IB.Dummy')
        ->where('Prefix', '=', $prefix)
        ->where('Number', '=', $number)
        ->groupBy('IP.Prefix', 'IP.Number', 'IP.Dummy', 'IB.ParkingLot')
        ->value('IB.ParkingLot');
        return $result;
    }
}
