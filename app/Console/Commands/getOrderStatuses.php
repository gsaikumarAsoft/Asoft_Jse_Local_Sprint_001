<?php

namespace App\Console\Commands;

use App\BrokerClientOrder;
use Illuminate\Console\Command;

class getOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl through [messageDownload and update order statuses]';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Run the logExecution Function and update statuses
        BrokerClientOrder::truncate();
        return "Wiped";
    }
}
