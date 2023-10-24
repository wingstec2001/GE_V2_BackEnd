<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;


/**
 * @property int $cc_did
 * @property string $contract_id
 * @property boolean $detail_id
 * @property string $material_id
 * @property int $contract_weight
 * @property int $contract_price
 * @property string $contract_note
 * @property string $selected_ids
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
//created by wangting
class ContractMaterialDetails extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_contract_material_details';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'cm_did';

    /**
     * @var array
     */
    protected $fillable = ['contract_id', 'detail_id', 'material_id', 'contract_goods_name', 'contract_weight', 'contract_price', 'selected_ids','contract_note', 'created_by', 'updated_by'];
   
}
