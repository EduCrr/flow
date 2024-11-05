<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandaAtrasadaRecorrencia extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_atrasadas_recorrentes';

    protected $fillable = [
        'recorrencia_id'
    ];

    public function demanda(){
        return $this->belongsTo(DemandaRecorrencia::class, 'recorrencia_id');
    }

}
