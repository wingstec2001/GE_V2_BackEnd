<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $product_id
 * @property string $stocked_dt
 * @property boolean $processed
 * @property int $crushed_weight
 * @property int $original_weight
 * @property int $aad_id
 * @property int $crushed_id
 * @property int $destination
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
class StockCrushed extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_stock_crushed';

    /**
     * @var array
     */
    protected $fillable = ['material_id', 'stocked_dt', 'processed', 'crushed_weight', 'original_weight', 'aad_id', 'crushed_id', 'destination', 'created_by', 'updated_by'];
}
