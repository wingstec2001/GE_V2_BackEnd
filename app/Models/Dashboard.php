<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $dashboard_id
 * @property boolean $location
 * @property string $dashboard_title
 * @property string $dashboard_image
 * @property string $text
 * @property boolean $fontsize
 * @property boolean $fontcolor
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class Dashboard extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 't_dashboard';

    /**
     * @var array
     */
    protected $fillable = ['dashboard_id', 'dashboard_title', 'dashboard_image', 'text', 'fontsize', 'fontcolor', 'created_by', 'updated_by'];

}
