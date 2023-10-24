<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $arrival_id
 * @property string $customer_id
 * @property boolean $actual_ampm
 * @property string $actual_date
 * @property string $arrival_note
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 * @property string $deleted_at
 * @property string $deleted_by
 */
//created by wangting
class ArrivalActual extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_arrival_actual';
    protected $primaryKey = 'arrival_id';

    /**
     * @var array
     */
    protected $fillable = ['customer_id', 'actual_date', 'actual_ampm', 'arrival_note', 'created_by', 'updated_by'];


    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id')->select(['customer_id', 'customer_name']);
    }

    public function Material()
    {
        return $this->hasMany(Material::class, 'material_id', 'material_id')->select(['material_id', 'material_name']);
    }

    // public function arrivalActualDetail()
    // {
    //     return $this->hasMany(ArrivalActualDetail::class, 'arrival_id', 'arrival_id');
    // }
    public function details()
    {
        return $this->hasMany(ArrivalActualDetail::class, 'arrival_id', 'arrival_id');
    }
}
