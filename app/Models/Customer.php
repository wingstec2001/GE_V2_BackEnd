<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $supplier
 * @property string $country_id
 * @property string $customer_id
 * @property string $customer_name
 * @property string $customer_name_eng
 * @property string $area_id
 * @property string $postcode
 * @property string $address1
 * @property string $address1_eng
 * @property string $address2
 * @property string $adderss2_eng
 * @property string $building
 * @property string $building_eng
 * @property string $manager_sei
 * @property string $manager_mei
 * @property string $manager_firstname
 * @property string $manager_lastname
 * @property string $mobile
 * @property string $email
 * @property string $tel
 * @property string $fax
 * @property string $website
 * @property string $line
 * @property string $wechat
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 * @property Country $Country
 * @property Order[] $Orders
 */
class Customer extends Model
{
    use  RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'm_customer';

    /**
     * @var array
     */
    protected $fillable = ['country_id', 'customer_id', 'customer_name','supplier', 'customer_name_eng', 'area_id', 'postcode', 'address1', 'address1_eng', 'address2', 'adderss2_eng', 'building', 'building_eng', 'manager_sei', 'manager_mei', 'manager_firstname', 'manager_lastname', 'mobile', 'email', 'tel', 'fax', 'website', 'line','wechat','created_by', 'updated_by'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Orders()
    {
        return $this->hasMany('App\Order', 'customer_id', 'customer_id');
    }
    public function Country()
    {
        return $this->hasMany(Country::class, 'country_id', 'country_id')->select(['country_id', 'country_name', 'country_code']);
    }
    public function Area()
    {
        return $this->hasMany(Area::class, 'area_id', 'area_id')->select(['area_id', 'area_name']);
    }
}
