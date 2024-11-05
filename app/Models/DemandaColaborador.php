<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DemandaColaborador extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_colaboradores';

    protected $fillable = [
        'usuario_id',
        'demanda_id'
    ];

}
