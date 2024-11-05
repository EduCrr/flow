<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Questionamento;
use App\Models\User;

class QuestionamentoLido extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'questionamentos_lidos';

    protected $fillable = [
        'usuario_id',
        'comentario_id',
        'visualizada',
        'marcado',
        'criado',
    ];

    public function usuario(){
        return $this->belongsTo(User::class, 'usuario_id', 'id');

    }

    public function questionamento(){
        return $this->belongsTo(Questionamento::class, 'comentario_id',);
    }

}
