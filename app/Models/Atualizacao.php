<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Atualizacao extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'atualizacoes';

    protected $fillable = [
        'titulo',
        'descricao',
        'criado',
        'excluido',
    ];

    public function usuarios() {
        return $this->belongsToMany(User::class, 'atualizacoes_usuarios', 'atualizacao_id', 'usuario_id');
    }
}
