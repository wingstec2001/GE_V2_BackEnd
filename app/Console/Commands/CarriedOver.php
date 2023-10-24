<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\business\MonthlyAggregate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class CarriedOver extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CarriedOver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CarriedOver';

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
            Log::info(Carbon::now()->format("Y-m-d H:i:s") . " 前月繰越処理開始...");
            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 前月繰越処理開始...");

            MonthlyAggregate::doUpdate();

            $this->info(Carbon::now()->format("Y-m-d H:i:s") . " 前月繰越処理終了。");
            Log::info(Carbon::now()->format("Y-m-d H:i:s") . " 前月繰越処理終了。");
        } catch (Exception $e) {

            $this->error(Carbon::now()->format("Y-m-d H:i:s") . " 前月繰越処理にエラーが発生しました：", $e->getMessage());
            Log::error(Carbon::now()->format("Y-m-d H:i:s") . " 前月繰越処理にエラーが発生しました：", $e->getMessage());
        }
    }
}
