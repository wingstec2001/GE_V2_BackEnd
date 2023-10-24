<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $employee_id
 * @property string $employee_sei
 * @property string $employee_mei
 * @property string $employee_seikn
 * @property string $employee_meikn
 * @property string $employee_birth
 * @property string $gender
 * @property string $mobile
 * @property string $address
 * @property string $jobtitle
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
class Employee extends Model
{
    use  RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'm_employee';

    /**
     * @var array
     */
    protected $fillable = ['employee_id', 'employee_sei', 'employee_mei', 'employee_seikn', 'employee_meikn', 'employee_birth', 'gender', 'mobile', 'address', 'jobtitle', 'created_by', 'updated_by'];

}
