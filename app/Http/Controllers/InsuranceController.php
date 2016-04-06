<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class InsuranceController extends Controller
{
    //url of mbr
    private $mbrUrl = "http://ec2-54-175-127-10.compute-1.amazonaws.com:3000/broker_ins";

    /**
     * @param Request $request - Used to get form data
     * @return Response - JSON-formatted response
     */
    public function receiveAppraisal(Request $request){
        //get the data from the request
        $mlsid = $request->input('mlsid');
        $appraisal = $request->input('appVal');
        $mortID = $request->input('mortID');
        
        //check to see if we have data for this property already
        $exists = app('db')->table('quotes')->where('mortid', $mortID)->first();
        if($exists){ //it exists, so update it
            app('db')->table('quotes')->where('mlsid', $mlsid)->update(['mlsid' => $mlsid, 'mlsid' => $mlsid, 'appraisal' => $appraisal]);
        }else{ //it doesn't exist - create it
            app('db')->table('quotes')->insert(['mlsid' => $mlsid, 'mortID' => $mortID, 'appraisal' => $appraisal]);
        }

        //check to see if we have all data, send if so
        $completed = $this->checkCompleted($mlsid);

        if($completed){ //if we tried to send
            return response()->json(["error" => $completed["error"], "forwardStatus" => "sent", "response" => $completed]);
        }else{ //if we're missing data, it has to be munCode
            return response()->json(["error" => false, "forwardStatus" => "waiting", "waitingOn" => ["munCode"]]);
        }
    }

    /**
     * @param Request $request - Used to get form data
     * @return Response - JSON-formatted response
     */
    public function receiveMunCode(Request $request){
        //get data from the request
        $mlsid = $request->input('mlsid');
        $mortID = $request->input('mortID');
        $munCode = $request->input('munCode');

        //check to see if we have data for this property already
        $exists = app('db')->table('quotes')->where('mortid', $mortID)->first();
        if($exists){ //it exists, so update it
            app('db')->table('quotes')->where('mortid', $mortID)->update(['mlsid' => $mlsid, 'munCode' => $munCode]);
        }else{ //it doesn't exist -  create it
            app('db')->table('quotes')->insert(['mortid' => $mortID, 'mlsid' => $mlsid, 'munCode' => $munCode]);
        }

        //check to see if we have all data, send if so
        $completed = $this->checkCompleted($mlsid);

        if($completed){ //if we tried to send
            return response()->json(["error" => $completed["error"], "forwardStatus" => "sent", "response" => $completed]);
        }else{ //if we're missing data, it has to be appraisal
            return response()->json(["error" => false, "forwardStatus" => "waiting", "waitingOn" => ["Appraisal"]]);
        }
    }

    /**
     * @param $mortID - The mortID to check the status of
     * @param bool $debug - whether or not we're in debug mode. Defaults to false.
     * @return array|bool - False if missing data. Array ["error", "response"] otherwise
     */
    private function checkCompleted($mortID, $debug = false){
        //check the db for the mortid in question
        $quote = app('db')->table('quotes')->where('mortid', $mortID)->first();

        //if we have all the data and haven't attempted to send before
        if($quote["mortid"] && $quote["appraisal"] && $quote["muncode"] && !$quote["sent"]) {
            //prepare the data
            $toSend = ['Mort_id' => $quote["mortid"], 'insured_value' => $quote["appraisal"]*$quote["muncode"], 'deductible' => 10000*($quote["muncode"]/10)];

            if ($debug) { //if we're in debug mode
                //just return the prepared data to sender
                return $toSend;
            } else { //not in debug mode
                //create GuzzleHttp client
                $client = new Client();
                try {
                    //send a request to MBR
                    $response = $client->request('POST', $this->mbrUrl, $toSend);

                    if ($response->getStatusCode() != 200) { //if the request returns anything except OK
                        $error = true;
                        $body = $response->getReasonPhrase();
                        //update the entry in the DB since we successfully sent the data
                        app('db')->table('quotes')->where('mortID', $mortID)->update(['sent' => true]);
                    } else { //if the request returns OK
                        $error = false;
                        $body = json_decode((string)$response->getBody());
                    }
                } catch (\Exception $e) { //the request failed
                    $error = true;
                    $body = "Could not establish connection to MBR";
                }

                return ["error" => $error, "response" => $body];
            }
        }else if($quote["sent"]){ //we've already sent this data
            return ["error" => true, "response" => "Data for this mortID has already been sent"];
        }else{ //we're missing data
            return false;
        }
    }

}
