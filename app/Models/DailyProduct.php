<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $target_date
 * @property string $product_id
 * @property int $weight_in
 * @property int $weight_out
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class DailyProduct extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_daily_product';

    /**
     * @var array
     */
    protected $fillable = ['target_date', 'product_id', 'weight_in', 'weight_out', 'created_at', 'created_by', 'updated_at', 'updated_by'];

}
