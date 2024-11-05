<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BriefingLido extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'briefing_lidos';

    protected $fillable = [
        'usuario_id',
        'demanda_id',
    ];

}
