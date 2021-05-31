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

class ManualExecutionBalanceUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $api;

    protected $activity;
 
    protected $messageDownload;
    protected $executionReports;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($messageDownload)
    {
        $this->job_status = 0;
        $this->api = new FunctionSet;
        $this->activity = new LogActivity;
        $this->messageDownload = json_encode($messageDownload);
        //Log::debug('ManualExecutionBalanceUpdate | ER Message Received: '. $this->messageDownload );         
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Create the data for this manual execution report
        $message = json_decode($this->messageDownload);
        $execution_report = $message->executionReports;
        if ($execution_report) {            
            ///Log::debug('ManualExecutionBalanceUpdate | executionReports received: ' . json_encode($execution_report) );
            $orderID = ""; 
            foreach ($execution_report as $a) {
                $b = [];
                foreach ($a as $key=>$value) {
                    $b[$key]=$value ;
                    if($key =="clOrdID") {
                        $orderID = $value;
                    }
                } 
                $this->api->manualLogExecution($b);
            }
        } else {
            Log::warning('ManualExecutionBalanceUpdate | No executionReports retrieved.' );
        }
    }
}