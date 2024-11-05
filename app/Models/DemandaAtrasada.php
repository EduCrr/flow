<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandaAtrasada extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_atrasadas';

    protected $fillable = [
        'demanda_id'
    ];

    public function demanda(){
        return $this->belongsTo(Demanda::class, 'demanda_id');
    }

}
