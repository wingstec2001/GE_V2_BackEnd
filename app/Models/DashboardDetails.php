<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $dd_id
 * @property string $dashboard_id
 * @property string $detail_id
 * @property boolean $fontsize
 * @property boolean $fontcolor
 * @property string $text
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 * @property string $deleted_at
 * @property string $deleted_by
 */
class DashboardDetails extends Model
{
    use RecordSignature;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_dashboard_details';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'dd_id';

    /**
     * @var array
     */
    protected $fillable = ['dashboard_id', 'detail_id', 'fontsize', 'fontcolor', 'text', 'created_by', 'updated_by', 'deleted_by'];

    public function Dashboard()
    {
        return $this->belongsTo(Dashboard::class, 'dashboard_id', 'dashboard_id');
    }
}
