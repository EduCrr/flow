<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UsuarioLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Demanda;
use App\Models\DemandaAtrasada;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
  public function login(){

    $user = Auth::User();

    // $demandas = Demanda::select('id', 'final', 'titulo')->where('excluido', null)
    // ->where('etapa_1', 1)
    // ->where('etapa_2', 1)
    // ->where('finalizada', 0)
    // ->where('entregue', 0)
    // ->with(['demandasUsuario' => function ($query) {
    //     $query->select('demandas_usuarios.id', 'email', 'nome');
    // }])
    // ->with(['demandasReabertas' => function ($query) {
    //     $query->where('finalizado', null);
    // }])->get();

    // if($demandas){
    //   $dataAtual = date('Y-m-d H:i:s');
    //   foreach($demandas as $key => $item){
    //     $demandasReabertas = $item->demandasReabertas;
    //     if ($demandasReabertas->count() > 0) {
    //       $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
    //       $item->final = $sugerido;
    //     }

    //     $findAtradaasa = DemandaAtrasada::where('demanda_id', $item->id)->count();

    //     if($findAtradaasa == 0){
    //       if($dataAtual > $item->final){
    //         $addAtrasada = new DemandaAtrasada();
    //         $addAtrasada->demanda_id = $item->id;
    //         $addAtrasada->save();
    //         $actionLink = route('Job', ['id' => $item->id]);
    //         $bodyEmail = 'A data final da demanda expirou. Por favor, verifique a data de entrega.';
    //         $titleEmail = 'O job '.$item->id. ': '.$item->titulo .'. Aviso: Data de entrega da demanda expirou!';
    //         foreach($item['demandasUsuario'] as $user){
    //           Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $user->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($user, $titleEmail) {
    //             $message->from('informativo@uniflow.app.br', 'Informativo UniFlow Unicasa')
    //             ->to($user->email)
    //             // ->bcc('eduardo.8poroito@gmail.com')
    //             ->subject($titleEmail);
    //           });
    //         }
    //       }
    //     }
    //   }
    // }

    if(Auth::check()){
      if($user->tipo == 'admin'){
        return redirect('/admin');
      }
      else if($user->tipo == 'colaborador'){
        return redirect('/dashboard');
      }
      else if($user->tipo == 'agencia'){
        return redirect('/');
      }
      else if($user->tipo == 'admin_8'){
        return redirect('/8poroito/admin');
      }
    }else{
      return view('login');
    }

  }

  public function login_action(Request $request)
  {
    $credentials = $request->validate([
      'email' => ['required', 'email'],
      'password' => ['required'],
    ], [
      'email.required' => 'Preencha o campo email.',
      'password.required' => 'Preencha o campo senha.',
    ]);

    if (!Auth::attempt($credentials)) {
        return back()->with([
            'error' => 'As credenciais informadas são inválidas. Verifique seus dados e tente novamente.',
        ])->withInput();
    }

    $user = Auth::user();
    if ($user->excluido !== null) {
      Auth::logout();
      return back()->with([
        'error' => 'As credenciais informadas são inválidas. Verifique seus dados e tente novamente.',
      ])->withInput();
    }

    $userLog = new UsuarioLog();
    $userLog->usuario_id = $user->id;
    $userLog->criado = date('Y-m-d H:i:s');
    $userLog->save();

    if($user->tipo == 'admin') {
      return redirect()->intended('/admin');
    } elseif ($user->tipo == 'colaborador') {
      return redirect()->intended('/dashboard');
    } elseif ($user->tipo == 'agencia') {
      return redirect()->intended('/');
    }
    elseif ($user->tipo == 'admin_8') {
      return redirect()->intended('/8poroito/admin');
    }
  }

  public function logout(){
    Auth::logout();
    return redirect('/login');
  }

  // public function cadastro(Request $request){
  //   // if(Auth::User()){
  //   //     return redirect('/marcas');
  //   // }
  //   // return view('cadastro');
  //   $user = Auth::User();

  //   if(Auth::check()){
  //     if($user->tipo === 'admin'){
  //       return redirect('/admin/pautas');
  //     }else if($user->tipo === 'user'){
  //       return redirect('/marcas');
  //     }
  //   }else{
  //     return view('cadastro');
  //   }
  // }

  // public function cadastro_action(Request $request){

  //   $request->validate([
  //     'nome' => 'required',
  //     'email' => 'required|email|unique:usuarios',
  //     'password' => 'required|min:3|confirmed'
  //   ]);

  //   $data = $request->only(['nome', 'email', 'password', 'criado']);
  //   $data['password'] = Hash::make($data['password']);
  //   $data['criado'] = Carbon::now();

  //   $userCreate = User::create($data);

  //   return redirect('/login');
  // }

}
