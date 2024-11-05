<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class MarcaColaborador extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'marcas_colaboradores';

    protected $fillable = [
        'marca_id',
        'usuario_id',
    ];

}
