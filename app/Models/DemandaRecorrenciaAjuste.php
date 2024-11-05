<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DemandaRecorrencia;


class DemandaRecorrenciaAjuste  extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_recorrentes_ajustes';

    protected $fillable = [
        'recorrencia_id',
        'data',
        'descricao',
        'atrasada',
        'em_pauta',
        'entregue',
        'data_entregue',
        'criado',
    ];

    public function DemandaRecorrencia(){
        return $this->belongsTo(DemandaRecorrencia::class, 'recorrencia_id');
    }
}
