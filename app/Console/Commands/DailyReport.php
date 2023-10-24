<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\business\ProductDailyReport;
use App\business\CrushedDailyReport;
use App\business\MaterialDailyReport;

class DailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DailyReport {--date=date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DailyReport {--date=date}';

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
     * @return int
     */
    public function handle()
    {
        $date = $this->option('date');
        $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 材料入出庫処理開始...");
        MaterialDailyReport::doUpdate($date);
        $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 材料入出庫処理終了...");

        $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 粉砕済入出庫処理開始...");
        CrushedDailyReport::doUpdate($date);
        $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 粉砕済入出庫処理終了...");

        $this->info(Carbon::now()->format("Y-m-d H:i:s") . " ペレット入出庫処理開始...");
        ProductDailyReport::doUpdate($date);
        $this->info(Carbon::now()->format("Y-m-d H:i:s") . " ペレット入出庫処理終了...");
        return Command::SUCCESS;
    }
}
