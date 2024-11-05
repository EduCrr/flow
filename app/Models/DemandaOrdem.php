<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DemandaOrdem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'demandas_ordem';

    protected $fillable = [
        'ordem',
        'usuario_id'
    ];
    
    public function ordemJob(){
        return $this->belongsTo(User::class);
    }

}
