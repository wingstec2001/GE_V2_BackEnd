<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $country_id
 * @property string $country_name
 * @property string $country_code
 * @property string $country_name_eng
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 * @property MCustomer[] $mCustomers
 */
class Country extends Model
{
    use  RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'm_country';

    /**
     * @var array
     */
    protected $fillable = ['country_id', 'country_name', 'country_code', 'country_name_eng', 'created_by', 'updated_by'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Customers()
    {
        return $this->hasMany('App\Customer', 'country_id', 'country_id');
    }
    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'country_id', 'country_id');
    }
}
