<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $aad_id
 * @property string $product_id
 * @property string $plan_dt
 * @property int $plan_weight
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
class CrushingPlan extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_crushing_plan';

    /**
     * @var array
     */
    protected $fillable = ['aad_id', 'product_id', 'plan_dt', 'plan_weight', 'created_by', 'updated_by'];

    public function Product()
    {
        return $this->hasMany(Product::class, 'product_id', 'product_id')->select(['product_id', 'product_name']);
    }
    
}
