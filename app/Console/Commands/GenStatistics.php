<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\business\MaterialDailyAggregate;
use App\business\CrushedDailyAggregate;
use App\business\ProductDailyAggregate;

class GenStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GenStatistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GenStatistics';

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
        try {
            Log::info(Carbon::now()->format("Y-m-d H:i:s") . " 日次処理開始...");
            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 日次処理開始...");

            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 材料入出庫日次処理開始...");
            MaterialDailyAggregate::doUpdate();
            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 材料入出庫日次処理終了...");

            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 粉砕済入出庫日次処理開始...");
            CrushedDailyAggregate::doUpdate();
            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 粉砕済入出庫日次処理終了...");

            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 製品入出庫日次処理開始...");
            ProductDailyAggregate::doUpdate();
            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 製品入出庫日次処理終了...");


            Log::info(Carbon::now()->format("Y-m-d H:i:s") . " 日次処理終了。");
            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 日次処理終了。");
        } catch (Exception $e) {

            $this->error(Carbon::now()->format("Y-m-d H:i:s") . " 日次処理にエラーが発生しました：" . $e->getMessage());
            Log::error(Carbon::now()->format("Y-m-d H:i:s") . " 日次処理にエラーが発生しました：" . $e->getMessage());
        }
    }
}
