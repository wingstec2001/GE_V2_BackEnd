<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $vanning_id
 * @property string $contract_id
 * @property string $customer_id
 * @property string $vanning_date
 * @property string $vanning_time
 * @property string $container_no
 * @property string $seal_no
 * @property boolean $vanning_status
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
//created by wangting
class Vanning extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_vanning';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'vanning_id';

    /**
     * @var array
     */
    protected $fillable = ['contract_id', 'customer_id', 'vanning_date', 'vanning_time', 'container_no', 'seal_no', 'vanning_status', 'country', 'area', 'created_by',  'updated_by'];

    public function vanningDetails()
    {
        return $this->hasMany(VanningDetails::class, 'vanning_id', 'vanning_id');
    }
}
