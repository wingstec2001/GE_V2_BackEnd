<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory,RecordSignature;
    protected $table = 'm_material';
    protected $fillable = [
        'material_id',
        'material_name',
        'material_img',
        'material_note',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
