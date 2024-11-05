<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Models\Notificacao;
use App\Models\AgenciaUsuario;
use App\Models\User;
use Cron\HoursField;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Carbon::setLocale($this->app->getLocale());


        view()->composer('*',function($view) {
            $user = Auth::User();
            $notifications = null;
            $notificationsCount = null;
            $agenciaLogged = null;
            $isAdminAg = null;
            $atualizationUser = null;
            if(Auth::check()){
                $isAdminAg = $user->adminUserAgencia()->whereNull('excluido')->count();
                $atualizationUser = $user->atualizacaoUsuario()->where('visto', '0')->count();

                if($user->tipo == 'colaborador' || $user->tipo == 'admin' || $user->tipo == 'admin_8' ){
                    $notifications = Notificacao::where('usuario_id', $user->id)->with(['demanda' => function ($query) {
                        $query->where('excluido', null);
                        $query->select('id', 'titulo');
                        }])->orderBy('criado', 'DESC')->orderBy('id', 'DESC')->limit(15)->get();

                        $notificationsCount = Notificacao::where('visualizada', '0')->where('usuario_id', $user->id)->count();

                }else if($user->tipo == 'agencia'){
                    $notifications = Notificacao::where(function ($query) use ($user) {
                        $query->where('usuario_id', $user->id);
                    })
                    ->with(['demanda' => function ($query) {
                        $query->where('excluido', null);
                        $query->select('id', 'titulo');
                    }])
                    ->orderBy('criado', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->limit(15)->get();

                    $notificationsCount = Notificacao::where(function ($query) use ($user) {
                        $query->where('usuario_id', $user->id);
                    })
                    ->where('visualizada', '0')
                    ->count();
                    $agenciaLogged = User::select('id')->where('id', $user->id)->with('usuariosAgencias')->first();
                }

            }

            $dataAtual = Carbon::now();
            $dataAtualRec = Carbon::now()->startOfDay();
            $dataAtual->second(0);
            $data16 = Carbon::now()->hour(16)->minute(0)->second(0);



            $view->with('dataAtual', $dataAtual);
            $view->with('notificationsMenu', $notifications);
            $view->with('notificationsCount', $notificationsCount);
            $view->with('agenciaLogged', $agenciaLogged);
            $view->with('loggedUser', Auth::user());
            $view->with('isAdminAg', $isAdminAg);
            $view->with('atualizationUser', $atualizationUser);
            $view->with('data16', $data16);
            $view->with('dataAtualRec', $dataAtualRec);

        });
    }
}
