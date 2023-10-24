<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $yyyymm
 * @property string $material_id
 * @property int $total_weight
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class GetsujiCrushed extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_getsuji_crushed';

    /**
     * @var array
     */
    protected $fillable = ['yyyymm', 'material_id', 'total_weight', 'created_by', 'updated_by'];
}
