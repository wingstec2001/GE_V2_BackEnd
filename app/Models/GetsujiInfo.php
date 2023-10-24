<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $yyyymm
 * @property string $product_id
 * @property int $materil_weight
 * @property int $crushed_weight
 * @property int $product_weight
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class GetsujiInfo extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_getsuji_info';

    /**
     * @var array
     */
    protected $fillable = ['yyyymm', 'product_id', 'material_weight', 'crushed_weight', 'product_weight', 'created_by', 'updated_by'];

}
