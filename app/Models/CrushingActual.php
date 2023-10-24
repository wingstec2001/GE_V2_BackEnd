<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $actual_date
 * @property string $material_id
 * @property int $actual_weight
 * @property boolean $blended
 * @property string $note
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
class CrushingActual extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_crushing_actual';
    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'crushed_id';
    /**
     * @var array
     */
    protected $fillable = ['actual_date', 'material_id', 'actual_weight', 'blended', 'note', 'created_by', 'updated_by'];

    // public function Product()
    // {
    //     return $this->hasMany(Product::class, 'product_id', 'product_id')->select(['product_id', 'product_name']);
    // }

    public function Material()
    {
        return $this->hasOne(Material::class, 'material_id', 'material_id')->select(['material_id', 'material_name']);
    }
}
