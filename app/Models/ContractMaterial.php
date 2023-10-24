<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $customer_id
 * @property string $contract_name
 * @property string $contract_date
 * @property boolean $contract_status
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
//created by wangting
class ContractMaterial extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_contract_material';

    /**
     * @var array
     */
    protected $fillable = ['customer_id', 'contract_name', 'contract_date', 'contract_status', 'created_by', 'updated_by'];

    public function contractDetails()
    {
        return $this->hasMany(ContractMaterialDetails::class, 'contract_id', 'id');
    }
}
