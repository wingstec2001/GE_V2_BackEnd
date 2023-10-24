<?php
namespace App\Models;

// use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordSignature;

/**
 * @property int $aad_id
 * @property int $arrival_id
 * @property string $product_id
 * @property string $arrival_date
 * @property int $arrival_weight
 * @property string $customer_id
 * @property string $note
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */

//  ペレット入荷
class ArrivalPellet extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_arrival_pellets';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'aad_id';

    /**
     * @var array
     */
    protected $fillable = ['arrival_id','product_id', 'arrival_date', 'arrival_weight',  'customer_id','note','created_by', 'updated_by'];

    public function Product()
    {
        return $this->hasOne(Material::class, 'product_id', 'product_id')->select(['product_id', 'product_name']);
    }
}
