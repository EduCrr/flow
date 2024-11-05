<?php

namespace App\Utils;
use App\Models\User;
use Carbon\Carbon;

class AplyFilters
{
    public static function applyFilters($query, $marca = null, $agencia = null, $usuario = null, $dataGrafico = null) {
        if ($marca) {
            $query->whereHas('marcas', function ($query) use ($marca) {
                $query->where('marcas.id', $marca);
                $query->where('marcas.excluido', null);
            });
        }
    
        if ($agencia) {
            $query->whereHas('agencia', function ($query) use ($agencia) {
                $query->where('agencias.id', $agencia);
                $query->where('agencias.excluido', null);
            });
        }
    
        if ($usuario) {
            $verifyUser = User::find($usuario);
    
            if ($verifyUser->tipo == 'agencia') {
                $query->where(function ($query) use ($verifyUser) {
                    $query->whereHas('demandasUsuario', function ($query) use ($verifyUser) {
                        $query->where('usuario_id', $verifyUser->id);
                    });
                });
            } else {
                $query->where(function ($query) use ($verifyUser) {
                    $query->where('criador_id', $verifyUser->id)
                        ->orWhereHas('demandaColaboradores', function ($query) use ($verifyUser) {
                            $query->where('usuario_id', $verifyUser->id);
                        });
                });
            }
        }

        if($dataGrafico){
            if ($dataGrafico != null || $dataGrafico != '') {
                $dataGraficoArray = explode(' - ', $dataGrafico);
            
                if (count($dataGraficoArray) === 2) {
                    $date = Carbon::createFromFormat('d/m/Y', $dataGraficoArray[0])->format('Y-m-d');
                    $endDate = Carbon::createFromFormat('d/m/Y', $dataGraficoArray[1])->format('Y-m-d');
            
                    $query->where(function($query) use ($date, $endDate) {
                        $query->where(function($query) use ($date, $endDate) {
                            $query->whereDate('inicio', '>=', $date)
                                ->whereDate('inicio', '<=', $endDate);
                        })->orWhere(function($query) use ($date, $endDate) {
                            $query->whereDate('final', '>=', $date)
                                ->whereDate('final', '<=', $endDate);
                        });
                    });

                    $query->where(function($query) use ($date, $endDate) {
                        $query->where(function($query) use ($date, $endDate) {
                            $query->whereDate('inicio', '>=', $date)
                                ->whereDate('inicio', '<=', $endDate);
                        })->orWhere(function($query) use ($date, $endDate) {
                            $query->whereDate('final', '>=', $date)
                                ->whereDate('final', '<=', $endDate);
                        });
                    });

                    $query->where(function($query) use ($date, $endDate) {
                        $query->where(function($query) use ($date, $endDate) {
                            $query->whereDate('inicio', '>=', $date)
                                ->whereDate('inicio', '<=', $endDate);
                        })->orWhere(function($query) use ($date, $endDate) {
                            $query->whereDate('final', '>=', $date)
                                ->whereDate('final', '<=', $endDate);
                        });
                    });
                }
            }
        }
    
        return $query;
    }
}
