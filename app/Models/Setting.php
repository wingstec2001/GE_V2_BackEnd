<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
  private  $workstarttime;

  public static function instance(): Setting
  {
    $Setting = Cache::remember('Setting', 60, function () {
      return self::Where('name', 'workstarttime')->first();
    });
    if ($Setting == null) {
      throw new \Exception('バンクサーバ設定情報をDBから取得できませんでした。');
    }
    $Setting->workstarttime = $Setting->value;
    return $Setting;
  }

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'm_setting';

  /**
   * @param Carbon\Carbon|string $accTime
   */
  public function getBusinessDate($accTime): Carbon
  {

    if (!($accTime instanceof Carbon)) {
      $accTime = new Carbon($accTime);
    }
    $date = Carbon::instance($accTime)->startOfDay();
    $time = Carbon::instance($accTime)->setDate(0, 0, 0);
    $workstarttime = Carbon::createFromFormat('H:i:s', $this->workstarttime)->setDate(0, 0, 0);

    if ($time->lt($workstarttime)) {
      $date->subDay();
    }

    return new Carbon($date->format('Y-m-d ') . $this->workstarttime);
  }

  /**
   * @param Carbon\Carbon|string $date
   */
  public function getStartDateTimeOfBusinessDate($date): Carbon
  {
    if (!($date instanceof Carbon)) {
      $date = new Carbon($date);
    }
    return new Carbon($date->format('Y-m-d ') . $this->workstarttime);
  }



  public function getNichijiStartDate()
  {
    $startDate = DB::table($this->table)->Where('name', 'nichiji_start_date')->first();

    return $startDate->value;
  }
}
