<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp as Guzzle;

class InsuranceController extends Controller
{

    private $mbrUrl = "https://mbr.laboratory.cf";

    public function receiveAppraisal(Request $request){
        $mlsid = $request->input('mlsid');
        $appraisal = $request->input('appVal');
        $mortID = $request->input('mortID');
        $exists = app('db')->table('appraisal')->where('mlsid', $mlsid)->first();
        if($exists){
            app('db')->table('appraisal')->where('mlsid', $mlsid)->update(['mortID' => $mortID, 'appraisal' => $appraisal]);
        }else{
            app('db')->table('appraisal')->insert(['mlsid' => $mlsid, 'mortID' => $mortID, 'appraisal' => $appraisal]);
        }
        if($this->checkCompleted($mlsid)){
            return response()->json(["error" => false, "forwardStatus" => "sent", "waitingOn" => []]);
        }else{
            return response()->json(["error" => false, "forwardStatus" => "waiting", "waitingOn" => ["munCode"]]);
        }
    }

    public function receiveMunCode(Request $request){
        $mlsid = $request->input('mlsid');
        $munCode = $request->input('munCode');
        $exists = app('db')->table('mundata')->where('mlsid', $mlsid)->first();
        if($exists){
            app('db')->table('mundata')->where('mlsid', $mlsid)->update(['munCode' => $munCode]);
        }else{
            app('db')->table('mundata')->insert(['mlsid' => $mlsid, 'munCode' => $munCode]);
        }
        if($this->checkCompleted($mlsid)){
            return response()->json(["error" => false, "forwardStatus" => "sent", "waitingOn" => []]);
        }else{
            return response()->json(["error" => false, "forwardStatus" => "waiting", "waitingOn" => ["Appraisal"]]);
        }
    }

    private function checkCompleted($mlsid, $debug = false){
        $appraisal = app('db')->table('appraisal')->select('mortid')->where('mlsid', $mlsid)->first();
        $munCode = app('db')->table('mundata')->select('muncode')->where('mlsid', $mlsid)->first();
        if($appraisal && $munCode){
            if($debug){
                return response()->json(['MortID' => $appraisal->mortid, 'insuredValue' => 523000, 'deductible' => 10000, 'name' => 'Bob']);
            }else {
                Request::create($this->mbrUrl, 'POST', ['MortID' => $appraisal->mortid, 'insuredValue' => 523000, 'deductible' => 10000, 'name' => 'Bob']);
            }
            return true;
        }else{
            return false;
        }
    }

}
