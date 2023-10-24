<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordSignature;

/**
 * @property int $id
 * @property string $reserve_id
 * @property string $product_id
 * @property string $reserve_name
 * @property int $reserve_weight
 * @property string $reserve_desc
 * @property int $reserve_price
 * @property integer $reserve_maximum
 * @property string $reserve_open_dt
 * @property string $reserve_comp_dt
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 * @property TOrder[] $tOrders
 */
class Reserve extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'm_reserve';

    /**
     * @var array
     */
    protected $fillable = ['reserve_id', 'product_id', 'reserve_name', 'reserve_weight', 'reserve_desc', 'reserve_price', 'reserve_maximum', 'reserve_open_dt', 'reserve_comp_dt', 'created_by', 'updated_by'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tOrders()
    {
        return $this->hasMany('App\Models\TOrder', 'reserve_id', 'reserve_id');
    }
    public function ReserveImages()
    {
        return $this->hasMany(ReserveImages::class, 'img_id', 'reserve_id');
    }
}
