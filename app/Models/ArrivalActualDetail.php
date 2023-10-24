<?php

namespace App\Models;

// use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordSignature;

/**
 * @property int $aad_id
 * @property int $arrival_id
 * @property string $material_id
 * @property string $arrival_date
 * @property int $arrival_weight
 * @property boolean $crushing_status
 * @property string $customer_id
 * @property boolean $blended
 * @property string $note
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class ArrivalActualDetail extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_arrival_details';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'aad_id';

    /**
     * @var array
     */
    protected $fillable = ['arrival_id','material_id', 'arrival_date', 'arrival_weight', 'crushing_status', 'customer_id', 'blended', 'note','created_by', 'updated_by'];

    public function Material()
    {
        return $this->hasOne(Material::class, 'material_id', 'material_id')->select(['material_id', 'material_name']);
    }
}
