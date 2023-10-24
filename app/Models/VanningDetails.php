<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $vd_id
 * @property string $vanning_id
 * @property boolean $detail_id
 * @property string $vanning_goods_name
 * @property int $vanning_weight
 * @property string $mark
 * @property boolean $label
 * @property string $country
 * @property string $area
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
//created by wangting
class VanningDetails extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_vanning_details';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'vd_id';

    /**
     * @var array
     */
    protected $fillable = ['vanning_id', 'detail_id', 'vanning_goods_name', 'vanning_weight', 'mark', 'label', 'country', 'area', 'created_by', 'updated_by'];

    public function Vanning()
    {
        return $this->belongsTo(Vanning::class, 'vanning_id', 'vanning_id');
    }
}
