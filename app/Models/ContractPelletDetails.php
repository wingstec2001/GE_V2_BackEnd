<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $cd_id
 * @property string $contract_id
 * @property boolean $detail_id
 * @property string $product_id
 * @property string $contract_goods_name
 * @property int $contract_weight
 * @property int $contract_price
 * @property string $contract_note
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
//created by wangting
class ContractPelletDetails extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_contract_pellet_details';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'cd_id';

    /**
     * @var array
     */
    protected $fillable = ['contract_id', 'detail_id', 'product_id', 'contract_goods_name', 'contract_weight', 'contract_price', 'contract_note', 'created_by', 'updated_by'];

    public function Contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id');
    }
}
