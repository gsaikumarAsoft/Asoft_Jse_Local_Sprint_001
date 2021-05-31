<?php

namespace App\Jobs;

use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\LocalBroker;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ExecutionBalanceUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $api;

    protected $activity;
 
    protected $senderSubID;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($senderSubID)
    {
        $this->api = new FunctionSet;
        $this->activity = new LogActivity;
        $this->senderSubID = $senderSubID;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Download all data from the message download wrapper
        $url = config('fixwrapper.base_url') . "api/messagedownload/download";
        $data = array(
            'BeginString' => 'FIX.4.2',
            "SenderSubID" => $this->senderSubID,
            "seqNum" => 0,
            'StartTime' => date('Y-m-d', time() -5 * 60 * 60) . " 00:00:00.000",
            'EndTime' => date('Y-m-d', time() -5 * 60 * 60) . " 23:59:59.000",
        );
/*
        $len = isset($cOTLdata[$data]) ? count($cOTLdata[$data]) : 0;
            
        if ($len == 0) {
            //do nothing
        } else {
            */
            $postdata = json_encode($data);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
        $result = curl_exec($ch);
            curl_close($ch);
            $request = json_decode($result, true);
            if (isset ($request['executionReports'])) {
                $execution_report = $request['executionReports'];

                if ($execution_report) { 
                    foreach ($execution_report as $a) {
                        //Log::debug('ExecutionBalanceUpdate | Processing executionReport: '. json_encode($a) ); 
                        $this->api->logExecution($a);
                    }
                } else {
                    Log::debug('ExecutionBalanceUpdate | No executionReports retrieved.' );
                }
            } else {
                Log::debug('ExecutionBalanceUpdate | Failure retrieving executionReports failed' );
            }
            
        //}


    }
}
