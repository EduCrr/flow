<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Demanda;
use App\Models\Notificacao;
use App\Models\DemandaImagem;
use App\Models\Agencia;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Marca;
use App\Models\Questionamento;
use App\Models\Resposta;
use App\Models\LinhaTempo;
use App\Models\AdminAgencia;
use App\Models\DemandaOrdem;
use App\Models\Atualizacao;
use App\Models\UsuarioLog;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'email',
        'password',
        'criado',
        'tipo',
        'token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $timestamps = false;
    protected $table = 'usuarios';


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function usuariosAgencias(){
        return $this->belongsToMany(Agencia::class, 'agencias_usuarios', 'usuario_id', 'agencia_id');
    }

    public function colaboradoresAgencias(){
        return $this->belongsToMany(Agencia::class, 'agencias_colaboradores', 'usuario_id', 'agencia_id');
    }

    public function usuarioDemandas(){
        return $this->belongsToMany(Demanda::class, 'demandas_usuarios', 'usuario_id', 'demanda_id');
    }

    public function usuarioOrdens(){
        return $this->belongsToMany(Demanda::class, 'demandas_ordem_jobs', 'usuario_id', 'demanda_id')->withPivot('ordem');
    }

    //demandas que o admin agencia cria
    public function agenciaUsuarioDemandas(){
        return $this->belongsToMany(Demanda::class, 'admin_demandas_usuarios', 'usuario_id', 'demanda_id');
    }

    public function colaboradorDemanda(){
        return $this->belongsToMany(Demanda::class, 'demandas_colaboradores', 'usuario_id', 'demanda_id');
    }

    public function marcas(){
        return $this->belongsToMany(Marca::class, 'marcas_usuarios', 'usuario_id', 'marca_id',);
    }

    public function marcasColaborador(){
        return $this->belongsToMany(Marca::class, 'marcas_colaboradores', 'usuario_id', 'marca_id',);
    }

    public function atualizacaoUsuario() {
        return $this->belongsToMany(Atualizacao::class, 'atualizacoes_usuarios', 'usuario_id', 'atualizacao_id');
    }

    public function imagens(){
        return $this->hasMany(DemandaImagem::class);
    }

    public function cidade(){
        return $this->belongsToMany(Cidade::class, 'informacoes_usuarios', 'usuario_id', 'cidade_id');
    }

    public function estado(){
        return $this->belongsToMany(Estado::class, 'informacoes_usuarios', 'usuario_id', 'estado_id');
    }

    public function usuarioQuestionamentos(){
        return $this->hasMany(Questionamento::class, 'usuario_id', 'id');
    }

    public function usuarioQuestionamentosMarcados(){
        return $this->hasMany(Questionamento::class, 'marcado_usuario_id', 'id');
    }

    public function linhaTempo(){
        return $this->hasMany(LinhaTempo::class);
    }

    public function respostas(){
        return $this->hasMany(Resposta::class);
    }

    public function usuarioRespostasMarcados(){
        return $this->hasMany(Resposta::class, 'marcado_usuario_id', 'id');
    }

    public function notificacoes(){
        return $this->hasMany(Notificacao::class);
    }

    public function adminUserAgencia(){
        return $this->hasMany(AdminAgencia::class, 'usuario_id');
    }

    public function usuarioLogs(){
        return $this->hasMany(UsuarioLog::class, 'usuario_id');
    }

    public function briefing(){
        return $this->hasMany(BriefingLido::class, 'usuario_id');
    }

    public function ordem(){
        return $this->hasOne(DemandaOrdem::class, 'usuario_id');
    }

}
