<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordSignature;

/**
 * @property int $id
 * @property string $reserve_id
 * @property integer $seq
 * @property string $reserve_image
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class ReserveImages extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_reserve_images';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = ['img_id', 'seq', 'reserve_image', 'created_by', 'updated_by'];

}
