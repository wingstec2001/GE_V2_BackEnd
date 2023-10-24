<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $customer_id
 * @property string $reserve_id
 * @property string $reserved_dt
 * @property int $reserved_count
 * @property string $reserved_note
 * @property boolean $reserved_result
 * @property boolean $contract_status
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_by
 * @property string $updated_at
 */
class Reservations extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_reservations';

    /**
     * @var array
     */
    protected $fillable = ['customer_id', 'reserve_id', 'reserved_dt', 'reserved_count', 'reserved_note', 'reserved_result', 'contract_status', 'created_by', 'updated_by'];
}
