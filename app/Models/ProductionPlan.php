<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property boolean $route_id
 * @property string $product_id
 * @property string $plan_date
 * @property int $plan_weight
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 */
class ProductionPlan extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_production_plan';

    /**
     * @var array
     */
    protected $fillable = ['route_id', 'product_id', 'plan_date', 'plan_weight', 'created_by', 'updated_by', 'deleted_by'];

    public function Product()
    {
        return $this->hasMany(Product::class, 'product_id', 'product_id')->select(['product_id', 'product_name']);
    }
}
