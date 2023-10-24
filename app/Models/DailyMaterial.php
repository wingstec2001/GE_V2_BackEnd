<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $target_date
 * @property string $material_id
 * @property int $weight_in
 * @property int $weight_out
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class DailyMaterial extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_daily_material';

    /**
     * @var array
     */
    protected $fillable = ['target_date', 'material_id', 'weight_in', 'weight_out', 'created_at', 'created_by', 'updated_at', 'updated_by'];
}
