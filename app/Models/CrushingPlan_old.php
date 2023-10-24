<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id 
 * @property string $material_id
 * @property string $plan_date
 * @property int $plan_weight
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 */
class CrushingPlan_old extends Model
{
    use SoftDeletes, RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_crushing_plan';

    /**
     * @var array
     */
    protected $fillable = ['material_id','aad_id', 'plan_dt', 'plan_weight', 'created_by', 'updated_by', 'deleted_by'];

    public function Material()
    {
        return $this->hasMany(Material::class, 'material_id', 'material_id')->select(['material_id', 'material_name']);
    }
}
