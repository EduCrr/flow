<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Demanda;
use App\Models\Uca;
use App\Models\Agencia;
use App\Models\User;

class Notificacao extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'notificacoes';

    protected $fillable = [
        'demanda_id',
        'uca_id',
        'usuario_id',
        'conteudo',
        'visualizada',
        'tipo',
        'tipo_referencia',
        'clicado',
        'criado',
    ];

    public function agencia(){
        return $this->belongsTo(Agencia::class);
    }

    public function demanda(){
        return $this->belongsTo(Demanda::class);
    }

    public function uca(){
        return $this->belongsTo(Uca::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
