<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Demanda;
use App\Models\DemandaRecorrencia;


class DemandaCampanhaRecorrencia  extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_campanhas_recorrentes';

    protected $fillable = [
        'demanda_id',
        'recorrencia_id',
        'criado',
    ];

    public function demandas(){
        return $this->belongsTo(Demanda::class, 'demanda_id');
    }

    public function recorrencias(){
        return $this->hasMany(DemandaRecorrencia::class, 'campanha_id');
    }
}
