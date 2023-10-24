<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $reserve_id
 * @property string $customer_id
 * @property int $order_weight
 * @property string $order_dt
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 * @property string $t_orderscol
 * @property string $deleted_at
 * @property string $deleted_by
 * @property Customer $Customer
 * @property Reserve $Reserve
 */
class Orders extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_orders';

    /**
     * @var array
     */
    protected $fillable = ['reserve_id', 'customer_id', 'order_weight', 'order_dt', 'created_by', 'updated_by', 't_orderscol', 'deleted_by'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id', 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Reserve()
    {
        return $this->belongsTo('App\Reserve', 'reserve_id', 'reserve_id');
    }
}
