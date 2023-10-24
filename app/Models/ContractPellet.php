<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $contract_date
 * @property string $customer_id
 * @property string $contract_name
 * @property boolean $contract_status
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
//created by wangting
class ContractPellet extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_contract_pellet';

    /**
     * @var array
     */
    protected $fillable = ['contract_date', 'customer_id', 'contract_name', 'contract_status', 'created_by', 'updated_by'];

    public function contractDetails()
    {
        return $this->hasMany(ContractPelletDetails::class, 'contract_id', 'id');
    }
}
