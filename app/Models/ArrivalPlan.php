<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $product_id
 * @property string $customer_id
 * @property string $plan_date
 * @property boolean $plan_ampm
 * @property int $plan_weight
 * @property string $plan_note
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class ArrivalPlan extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_arrival_plan';

    /**
     * @var array
     */
    protected $fillable = ['product_id', 'customer_id', 'plan_date', 'plan_ampm', 'plan_weight', 'plan_note', 'created_by', 'updated_by'];

    public function Product()
    {
        return $this->hasMany(Product::class, 'product_id', 'product_id')->select(['product_id', 'product_name']);
    }
    
    public function Customer()
    {
        return $this->hasMany(Customer::class, 'customer_id', 'customer_id')->select(['customer_id', 'customer_name']);
    }
}
