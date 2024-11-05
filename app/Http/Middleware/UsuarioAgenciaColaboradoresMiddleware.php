<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AgenciaUsuario;
use App\Models\Demanda;

class UsuarioAgenciaColaboradoresMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(Auth::check()){
            $user = Auth::user();

            $agencyUser = AgenciaUsuario::where('usuario_id', $user->id)->first();
            $verifyUser = Demanda::where('agencia_id', $agencyUser->agencia_id)->where('excluido', null)->exists();

            if ($verifyUser){
                return $next($request);
            }else{
                return redirect()->route('login')->with('error', 'Você precisa efetuar o login para continuar.');
            }
        }
    }
}
