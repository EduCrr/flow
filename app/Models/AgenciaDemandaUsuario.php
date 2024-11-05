<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Demanda;

class AgenciaDemandaUsuario extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'admin_demandas_usuarios';

    protected $fillable = [
        'usuario_id',
        'demanda_id',
    ];

    public function demandas(){
        return $this->belongsTo(Demanda::class);
    }

}
