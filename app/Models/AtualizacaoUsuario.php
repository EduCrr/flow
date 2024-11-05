<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Atualizacao;

class AtualizacaoUsuario extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'atualizacoes_usuarios';

    protected $fillable = [
        'usuario_id',
        'atualizacao_id',
        'visto',
    ];

}
