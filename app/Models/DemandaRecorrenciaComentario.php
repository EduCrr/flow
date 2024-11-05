<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DemandaRecorrencia;
use App\Models\DemandaRecorrenciaComentarioLido;


class DemandaRecorrenciaComentario  extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_recorrentes_comentarios';

    protected $fillable = [
        'recorrencia_id',
        'usuario_id',
        'descricao',
        'visualizada',
        'criado',
    ];

    public function DemandaRecorrencia(){
        return $this->belongsTo(DemandaRecorrencia::class, 'recorrencia_id', 'id');
    }

    public function usuario(){
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function lidos(){
        return $this->hasMany(DemandaRecorrenciaComentarioLido::class, 'comentario_id', 'id');
    }
}
