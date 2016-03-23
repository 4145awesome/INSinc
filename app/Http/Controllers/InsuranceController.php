<?php

namespace App\Http\Controllers;

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
        $this->checkCompleted($mlsid);
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
        $this->checkCompleted($mlsid);
    }

    private function checkCompleted($mlsid){
        $appraisal = app('db')->table('appraisal')->where('mlsid', $mlsid)->first();
        $munCode = app('db')->table('mundata')->where('mlsid', $mlsid)->first();
        if($appraisal && $munCode){
            Request::create($this->mbrUrl, 'POST', ['MortID' => $appraisal->mortID, 'insuredValue' => 523000, 'deductible' => 10000, 'name' => 'Bob']);
        }
    }

}
