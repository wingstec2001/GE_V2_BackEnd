<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    # 商用環境以外だった場合、SQLログを出力する
    // if (config('app.env') !== 'production') {
    //   DB::listen(function ($query) {
    //     // Log::info("Query Time:{$query->time}ms] $query->sql ",$query->bindings);
    //     Log::info(
    //       $query->sql,
    //       $query->bindings,
    //       $query->time
    //     );
    //     // var_dump( $query->sql);
    //   });
    // }
  }
}
