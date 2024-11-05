<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Marca;
use App\Models\Notificacao;
use App\Models\Alteracao;
use App\Models\Comentario;
use App\Models\DemandaImagem;
use App\Models\DemandaUsuario;
use App\Models\User;
use App\Models\LinhaTempo;
use App\Models\Agencia;
use App\Models\DemandaTempo;
use App\Models\DemandaStatu;
use App\Models\Questionamento;
use App\Models\DemandaReaberta;
use App\Models\AgenciaDemandaUsuario;
use App\Models\DemandaComplemento;
use App\Models\DemandaCampanhaRecorrencia;

class Demanda extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas';

    protected $fillable = [
        'titulo',
        'usuario_id',
        'criador_id',
        'agencia_id',
        'status',
        'inicio',
        'final',
        'prioridade',
        'cor',
        'drive',
        'etapa_1',
        'etapa_2',
        'criado',
        'em_pauta',
        'entregue',
        'recorrente',
        'finalizada_recorrencia',
        'finalizada',
        'atrasada',
        'recebido',
        'entregue_recebido',
        'pausado',
        'excluido'

    ];

    public function marcas(){
        return $this->belongsToMany(Marca::class, 'demandas_marcas', 'demanda_id', 'marca_id');
    }

    public function alteracoes(){
        return $this->hasMany(Alteracao::class);
    }

    public function notificacoes(){
        return $this->hasMany(Notificacao::class);
    }

    public function comentarios(){
        return $this->hasMany(Comentario::class);
    }

    public function status(){
        return $this->hasMany(DemandaStatu::class);
    }

    public function linhaTempo(){
        return $this->hasMany(LinhaTempo::class);
    }

    public function imagens(){
        return $this->hasMany(DemandaImagem::class);
    }

    public function prazosDaPauta(){
        return $this->hasMany(DemandaTempo::class);
    }

    public function questionamentos(){
        return $this->hasMany(Questionamento::class);
    }

    public function descricoes(){
        return $this->hasOne(DemandaComplemento::class, 'demanda_id');
    }

    public function atrasada(){
        return $this->hasOne(DemandaAtrasada::class, 'demanda_id');
    }

    public function demandasReabertas(){
        return $this->hasMany(DemandaReaberta::class);
    }

    public function demandaRecorrencias(){
        return $this->hasMany(DemandaCampanhaRecorrencia::class, 'demanda_id');
    }

    public function briefing(){
        return $this->hasMany(BriefingLido::class, 'demanda_id');
    }

    public function criador(){
        return $this->belongsTo(User::class);
    }

    public function subCriador(){
        return $this->belongsTo(User::class);
    }

    public function agencia(){
        return $this->belongsTo(Agencia::class);
    }

    public function demandasUsuario(){
        return $this->belongsToMany(User::class, 'demandas_usuarios', 'demanda_id', 'usuario_id');
    }

    //demandas que o admin agencia cria
    public function demandasUsuarioAdmin(){
        return $this->belongsToMany(User::class, 'admin_demandas_usuarios', 'demanda_id', 'usuario_id');
    }

    public function marcasDemandas(){
        return $this->belongsToMany(Marca::class, 'demandas_marcas', 'demanda_id', 'marca_id');
    }

    //demandas que possuem mais que 1 colaborador
    public function demandaColaboradores(){
        return $this->belongsToMany(User::class, 'demandas_colaboradores', 'demanda_id', 'usuario_id');
    }

    public function demandaOrdem(){
        return $this->belongsToMany(User::class, 'demandas_ordem_jobs', 'demanda_id', 'usuario_id')->withPivot('ordem');
     }
}
