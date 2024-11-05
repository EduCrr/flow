<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Demanda;

class DemandaComplemento extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_complementos';

    protected $fillable = [
        'demanda_id',
        'metas_objetivos',
        'peças',
        'formato',
        'formato_texto',
        'dimensoes',
        'descricao',

    ];

    public function demanda(){
        return $this->belongsTo(Demanda::class, 'demanda_id');
    }

}
