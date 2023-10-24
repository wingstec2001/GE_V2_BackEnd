<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $area_id
 * @property string $area_name
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class Area extends Model
{
    use  RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'm_area';

    /**
     * @var array
     */
    protected $fillable = ['area_id', 'area_name', 'created_by', 'updated_by'];
     /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Customers()
    {
        return $this->hasMany('App\Customer', 'area_id', 'area_id');
    }
    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'area_id', 'area_id');
    }

}
