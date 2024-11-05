<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Atualizacao;
use App\Models\AtualizacaoUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AtualizacoesController extends Controller
{
    public function index(){
        $atualizations = Atualizacao::where('excluido', null)->orderBy('criado', 'DESC')->get();
        $user = Auth::User();

        $findUserAtualization = AtualizacaoUsuario::where('usuario_id', $user->id)->where('visto', 0)->get();

        if(count($findUserAtualization) > 0){
            foreach($findUserAtualization as $item){
                $item->visto = 1;
                $item->save();
            }
        }

        return view('Atualizacoes/index', [
            'atualizations' => $atualizations
        ]);
    }

    public function criar(Request $request){
        $user = Auth::User();
        if($user->id == '39'){
            if ($request->isMethod('post')) {

                $validator = Validator::make($request->all(),[
                    'titulo' => 'required|max:255',
                    'descricao' => 'required',
                ],[
                    'titulo.required' => 'Preencha o campo título.',
                    'titulo.max' => 'O campo título deve ter no máximo 255 caracteres.',
                    'descricao.required' => 'Preencha o campo descrição.',
                    ]
                );

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first(),
                        'errors' => $validator->errors(),
                    ], Response::HTTP_BAD_REQUEST);
                }

                if(!$validator->fails()){

                    $newAtualization = new Atualizacao();
                    $newAtualization->titulo = $request->titulo;
                    $newAtualization->descricao = $request->descricao;
                    $newAtualization->criado = date('Y-m-d H:i:s');
                    $newAtualization->save();

                    $user = User::select('id')->where('excluido', null)->get();

                    $deleteNotify = AtualizacaoUsuario::where('visto', '1')->delete();

                    $atualizationUsers = [];
                    foreach($user as $usuario) {
                        $atualizationUsers[] = [
                            'usuario_id' => $usuario->id,
                            'atualizacao_id' => $newAtualization->id,
                        ];

                    }

                    AtualizacaoUsuario::insert($atualizationUsers);

                    return response()->json([
                        'success' => true,
                        'message' => 'Mensagem enviada com sucesso!',
                    ], Response::HTTP_OK);

                }
            }

            return view('Atualizacoes/criar', [
            ]);
        }else{
            return redirect()->route('Atualizacoes.index');
        }

    }

    public function editar(Request $request, $id){
        $user = Auth::User();
        if($user->id == '39'){
            $getwAtualization = Atualizacao::where('id', $id)->first();

            if ($request->isMethod('post')) {

                $validator = Validator::make($request->all(),[
                    'titulo' => 'required|max:255',
                    'descricao' => 'required',
                ],[
                    'titulo.required' => 'Preencha o campo título.',
                    'titulo.max' => 'O campo título deve ter no máximo 255 caracteres.',
                    'descricao.required' => 'Preencha o campo descrição.',
                    ]
                );

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first(),
                        'errors' => $validator->errors(),
                    ], Response::HTTP_BAD_REQUEST);
                }

                if(!$validator->fails()){
                    if($getwAtualization){
                        $getwAtualization->titulo = $request->titulo;
                        $getwAtualization->descricao = $request->descricao;
                        $getwAtualization->save();
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Mensagem enviada com sucesso!',
                    ], Response::HTTP_OK);

                }
            }

            if($getwAtualization){
                return view('Atualizacoes/editar', [
                    'atualizations' => $getwAtualization
                ]);
            }else{
                return redirect()->route('Atualizacoes.index');
            }
        }else{
            return redirect()->route('Atualizacoes.index');
        }

    }

    public function uploadImage(Request $request)
    {
        $file = $request->file('file');

        // Verifica se o arquivo foi recebido
        if (!$file->isValid()) {
            return response()->json(['error' => 'Arquivo inválido'], 400);
        }

        // Obtém a extensão e o nome original do arquivo
        $extension = $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();

        // Define o diretório de destino para a imagem
        $destImg = public_path('assets/files');

        // Garante que o nome do arquivo seja único
        $i = 1;
        $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);
        while (file_exists($destImg . '/' . $fileName)) {
            $fileName = $baseFileName . '_' . $i . '.' . $extension;
            $i++;
        }

        // Move o arquivo para o diretório de destino
        $file->move($destImg, $fileName);

        // Retorna a URL completa da imagem
        return response()->json([
            'location' => asset('assets/files/' . $fileName),
        ]);
    }


    public function excluir(Request $request, $id){
        $user = Auth::User();
        if($user->id == '39'){
            $deleteAtualization = Atualizacao::where('id', $id)->first();
            $deleteAtualization->excluido = date('Y-m-d H:i:s');
            $deleteAtualization->save();
        }

        return redirect()->route('Atualizacoes.index');

    }

}
