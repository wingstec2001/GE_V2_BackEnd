<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordSignature;

/**
 * @property int $id
 * @property string $blended_dt
 * @property string $material_id
 * @property int $blended_weight
 * @property int $stock_crushed_id
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class Blender extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_blender';

    /**
     * @var array
     */
    protected $fillable = ['blended_dt', 'material_id', 'blended_weight', 'stock_crushed_id', 'created_at', 'created_by', 'updated_at', 'updated_by'];

    public function Material()
    {
        return $this->hasOne(Material::class, 'material_id', 'material_id')->select(['material_id', 'material_name']);
    }
}
