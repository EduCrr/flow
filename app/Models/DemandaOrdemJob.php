<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandaOrdemJob extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_ordem_jobs';

    protected $fillable = [
        'ordem',
        'usuario_id',
        'demanda_id'
    ];

}
