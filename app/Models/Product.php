<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $product_id
 * @property string $product_name
 * @property string $product_description
 * @property string $product_img
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 * @property MReserve[] $mReserves
 */
class Product extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'm_product';

    /**
     * @var array
     */
    protected $fillable = ['product_id', 'product_name', 'product_description', 'product_img', 'created_by', 'updated_by'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Reserves()
    {
        return $this->hasMany('App\Reserve', 'product_id', 'product_id');
    }

    public function Production()
    {
        return $this->belongsTo(Production::class, 'product_id', 'product_id');
    }
}
