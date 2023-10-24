<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordSignature;

/**
 * @property int $id
 * @property string $bid_id
 * @property string $product_id
 * @property string $bid_name
 * @property string $bid_desc
 * @property int $bid_weight
 * @property int $bid_min_price
 * @property integer $bid_max_c_cnt
 * @property string $bid_comp_dt
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
class Bid extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'm_bid';

    /**
     * @var array
     */
    protected $fillable = ['bid_id', 'product_id', 'bid_name', 'bid_desc', 'bid_weight', 'bid_min_price', 'bid_max_c_cnt', 'bid_open_dt', 'bid_comp_dt', 'created_by', 'updated_by'];

    public function ReserveImages()
    {
        return $this->hasMany(ReserveImages::class, 'img_id', 'bid_id');
    }
}
