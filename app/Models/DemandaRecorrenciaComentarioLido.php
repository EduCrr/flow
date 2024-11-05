<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DemandaRecorrenciaComentarioLido  extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_recorrentes_comentarios_lidos';

    protected $fillable = [
        'comentario_id',
        'usuario_id',
        'descricao',
        'visualizada',
        'criado',
    ];

    public function DemandaRecorrencia(){
        return $this->belongsTo(DemandaRecorrenciaComentario::class, 'comentario_id', 'id');
    }

    public function usuario(){
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
