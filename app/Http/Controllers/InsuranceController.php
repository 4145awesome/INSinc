<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class InsuranceController extends Controller
{

    private $mbrUrl = "http://ec2-54-175-127-10.compute-1.amazonaws.com:3000/broker_ins";

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
        $completed = $this->checkCompleted($mlsid);
        if($completed){
            return response()->json(["error" => false, "forwardStatus" => "sent", "response" => $completed]);
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
        $completed = $this->checkCompleted($mlsid);
        if($completed){
            return response()->json(["error" => false, "forwardStatus" => "sent", "response" => $completed]);
        }else{
            return response()->json(["error" => false, "forwardStatus" => "waiting", "waitingOn" => ["Appraisal"]]);
        }
    }

    private function checkCompleted($mlsid, $debug = false){
        $appraisal = app('db')->table('appraisal')->select('mortid')->where('mlsid', $mlsid)->first();
        $munCode = app('db')->table('mundata')->select('muncode')->where('mlsid', $mlsid)->first();
        if($appraisal && $munCode){
            if($debug){
                return ['first_name' => 'Kevin', 'last_name' => 'Gee', 'Mort_id' => 'kMF90909b', 'insured_value' => '13600', 'deductible' => '2000'];
            }else {
                $client = new Client();
                try {
                    $response = $client->request('POST', $this->mbrUrl, ['first_name' => 'Kevin', 'last_name' => 'Gee', 'Mort_id' => 'kMF90909b', 'insured_value' => '13600', 'deductible' => '2000']);
                    if($response->getStatusCode() != 200){
                        $error = true;
                        $body = $response->getReasonPhrase();
                    }else{
                        $error = false;
                        $body = json_decode((string) $response->getBody());
                    }
                }catch(ConnectException $e){
                    $error = true;
                    $body = "Could not establish connection to MBR";
                }

                return ["error" => $error, "response" => $body];
            }
        }else{
            return false;
        }
    }

}
