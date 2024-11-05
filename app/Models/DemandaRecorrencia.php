<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DemandaCampanhaRecorrencia;
use App\Models\DemandaRecorrenciaAjuste;
use App\Models\DemandaRecorrenciaComentario;


class DemandaRecorrencia  extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_recorrentes';

    protected $fillable = [
        'campanha_id',
        'titulo',
        'data',
        'descricao',
        'atrasada',
        'tipo',
        'em_pauta',
        'em_alteracao',
        'entregue',
        'finalizado',
        'data_entrega',
        'criado',
    ];

    public function DemandaCampanhaRecorrencia(){
        return $this->belongsTo(DemandaCampanhaRecorrencia::class, 'campanha_id');
    }

    public function ajustes(){
        return $this->hasMany(DemandaRecorrenciaAjuste::class, 'recorrencia_id');
    }

    public function comentarios(){
        return $this->hasMany(DemandaRecorrenciaComentario::class, 'recorrencia_id');
    }

    public function atrasada(){
        return $this->hasOne(DemandaAtrasadaRecorrencia::class, 'demanda_id');
    }
}
