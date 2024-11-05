<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\DemandasController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\AdminDemandasController;
use App\Http\Controllers\NotificacoesController;
use App\Http\Controllers\ComentariosController;
use App\Http\Controllers\RespostasController;
use App\Http\Controllers\AdminAgenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AtualizacoesController;
use App\Http\Controllers\ApresentacoesController;
use App\Http\Controllers\Admin8poroitoController;
use App\Http\Controllers\RecorrenciasController;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//admin da 8
Route::middleware(['auth', 'isAdmin8'])->group(function(){
    //add

    Route::get('/8poroito/admin/agencia/adicionar', [Admin8poroitoController::class, 'agency'])->name('Admin.agencia');
    Route::get('/8poroito/admin/marca/adicionar', [Admin8poroitoController::class, 'brand'])->name('Admin.marca');
    Route::get('/8poroito/admin/usuario/adicionar', [Admin8poroitoController::class, 'user'])->name('Admin.usuario');
    Route::post('/8poroito/admin/agencia/adicionar', [Admin8poroitoController::class, 'agencyCreate'])->name('Admin.agencia_adicionar');
    Route::post('/8poroito/admin/usuario/adicionar', [Admin8poroitoController::class, 'userCreate'])->name('Admin.usuario_adicionar');
    Route::post('/8poroito/admin/marca/adicionar', [Admin8poroitoController::class, 'brandCreate'])->name('Admin.marca_adicionar');

    //edit
    Route::get('/8poroito/admin/agencia/editar/{id}', [Admin8poroitoController::class, 'agencyEdit'])->name('Admin.agencia_editar');
    Route::get('/8poroito/admin/marca/editar/{id}', [Admin8poroitoController::class, 'brandEdit'])->name('Admin.marca_editar');
    Route::get('/8poroito/admin/usuario/editar/{id}', [Admin8poroitoController::class, 'userEdit'])->name('Admin.usuario_editar');
    Route::post('/8poroito/admin/agencia/editar/{id}', [Admin8poroitoController::class, 'agencyEditAction'])->name('Admin.agencia_editar_action');
    Route::post('/8poroito/admin/usuario/editar/{id}', [Admin8poroitoController::class, 'userEditAction'])->name('Admin.usuario_editar_action');
    Route::post('/8poroito/admin/marca/editar/{id}', [Admin8poroitoController::class, 'brandEditAction'])->name('Admin.marca_editar_action');

    //delete
    Route::get('/8poroito/admin/agencia/delete/{id}', [Admin8poroitoController::class, 'agencyDelete'])->name('Admin.agencia_delete_action');
    Route::get('/8poroito/admin/marca/delete/{id}', [Admin8poroitoController::class, 'brandDelete'])->name('Admin.marca_delete_action');
    Route::get('/8poroito/admin/usuario/delete/{id}', [Admin8poroitoController::class, 'userDelete'])->name('Admin.usuario_delete_action');

    Route::get('/8poroito/admin/agencias', [Admin8poroitoController::class, 'agencysAll'])->name('Admin.agencias');
    Route::get('/8poroito/admin/marcas', [Admin8poroitoController::class, 'brandsAll'])->name('Admin.marcas');
    Route::get('/8poroito/admin/usuarios', [Admin8poroitoController::class, 'usersAll'])->name('Admin.usuarios');
    //graphs
    Route::get('/8poroito/admin/agencia/graficos/{id}', [Admin8poroitoController::class, 'agencysGraphs'])->name('Admin.agencia_graficos');
    Route::get('admin/export/{id}', [Admin8poroitoController::class, 'exportDays'])->name('admin.export');
    Route::get('admin/export/jobs/{id}', [Admin8poroitoController::class, 'exportJobs'])->name('admin.export.jobs');
    Route::get('admin/export/prazos/{id}', [Admin8poroitoController::class, 'exportPrazos'])->name('admin.export.prazos');

    // Route::get('/admin/ordem', [Admin8poroitoController::class, 'ordemAdmin'])->name('Admin.ordem');

    //apresentaçoes
    Route::get('/8poroito/admin/dashboard', [ApresentacoesController::class, 'dashboard'])->name('Admin.apresentacoes-marca');

    Route::get('/8poroito/admin', [Admin8poroitoController::class, 'index'])->name('8poroito_Admin');
    Route::get('/8poroito/admin/jobs', [Admin8poroitoController::class, 'jobs'])->name('8poroito_Admin.jobs');
    Route::get('/8poroito/admin/etapas', [Admin8poroitoController::class, 'stages'])->name('8poroito_Admin.Etapas');
    Route::get('/8poroito/atualizar-grafico', [Admin8poroitoController::class, 'chart'])->name('8poroito_Admin.chart');
    Route::get('/8poroito/admin/estados/{id}', [Admin8poroitoController::class, 'getCityByStatesAdmin'])->name('8poroito_Admin.getEstadosAdmin');

});

//admin colaborador
Route::middleware(['auth', 'isAdmin'])->group(function(){
    Route::get('/admin', [AdminDemandasController::class, 'index'])->name('Admin');
    Route::get('/admin/estados/{id}', [AdminDemandasController::class, 'getCityByStatesAdmin'])->name('getEstadosAdmin');
    Route::get('/admin/jobs', [AdminDemandasController::class, 'jobs'])->name('Admin.jobs');
    Route::get('/admin/etapas', [AdminDemandasController::class, 'stages'])->name('Admin.Etapas');
    Route::get('/atualizar-grafico', [AdminDemandasController::class, 'chart'])->name('Admin.chart');
});

//agencia
Route::middleware(['auth', 'isAgencia'])->group(function(){
    Route::get('/', [HomeController::class, 'homeIndex'])->name('index');
    Route::get('/minhas-pautas', [DemandasController::class, 'findAll'])->name('Pautas');
});

Route::middleware(['auth', 'hasAgency'])->group(function(){
    Route::post('/status/demanda/{id}', [DemandasController::class, 'statusSelect'])->name('status');
    // Route::get('/prioridade/agencia', [DemandasController::class, 'changeCategoryAg'])->name('Prioridade.agencia');
    Route::post('/changeStatus', [DemandasController::class, 'changeStatus'])->name('status');
    Route::post('/changeStatusPauta/{id}', [DemandasController::class, 'changeStatusPauta'])->name('Pauta.criar_tempo');
    Route::post('/editStatusPauta/{id}', [DemandasController::class, 'editStatusPauta'])->name('Pauta_editar');
    // Route::post('/changeStatusEntrega/{id}', [DemandasController::class, 'changeStatusEntrega'])->name('Status.entrega');
    Route::post('/demanda/titulo/{id}', [DemandasController::class, 'changeDemandatitle'])->name('Demanda_titulo');
    Route::post('/pauta/finalizar/{id}', [DemandasController::class, 'finalizeAgenda'])->name('Pauta.finalizar_tempo');
    Route::post('/pauta/iniciar/{id}', [DemandasController::class, 'startAgenda'])->name('Pauta.iniciar_tempo');
    Route::post('/pauta/aceitar/tempo/agencia/{id}', [DemandasController::class, 'acceptTime'])->name('Pauta.Aceitar_tempo_agencia');
    Route::post('/pauta/receber/{id}', [DemandasController::class, 'receive'])->name('Pauta.receber');
    Route::post('/pauta/receber/alteracao/{id}', [DemandasController::class, 'receiveAlteration'])->name('Pauta.receber_alteracao');
    // Route::get('/agencia/ordem', [HomeController::class, 'ordemAgencia'])->name('Agencia.ordem');
});

//colaborador
Route::middleware(['auth', 'isColaborador'])->group(function () {
    // Route::get('/colaborador/ordem', [ColaboradorController::class, 'ordemColaborador'])->name('Colaborador.ordem');
    Route::get('/dashboard', [ColaboradorController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/jobs', [ColaboradorController::class, 'jobs'])->name('Jobs');
});

Route::middleware(['auth', 'isColaboradorAdmin'])->group(function () {
    Route::get('/dashboard/etapas', [ColaboradorController::class, 'stages'])->name('Etapas');
    Route::get('/dashboard/criar/etapa/1', [ColaboradorController::class, 'create'])->name('Job.criar');
    Route::get('/dashboard/criar/job/{id}/etapa/2', [ColaboradorController::class, 'createStage2'])->name('Job.criar_etapa_2');
    Route::get('/dashboard/deletar/job/{id}/etapa/1', [ColaboradorController::class, 'deleteStage1'])->name('Job.deletar_etapa_1');
    Route::get('/dashboard/job/editar/{id}', [ColaboradorController::class, 'edit'])->name('Job.editar');
    Route::get('/dashboard/job/copiar/{id}', [ColaboradorController::class, 'copy'])->name('Job.copiar');
    // Route::get('/dashboard/delete/{id}', [ColaboradorController::class, 'delete'])->name('Job.delete');
    Route::post('/dashboard/delete/{id}', [ColaboradorController::class, 'delete'])->name('Job.delete');
    Route::post('/dashboard/criar-action', [ColaboradorController::class, 'createAction'])->name('Job.criar_action');
    Route::post('/dashboard/job/{id}/criar-action-etapa-2', [ColaboradorController::class, 'createActionStage2'])->name('Job.criar_action_stage_2');
    Route::post('/dashboard/editar/{id}', [ColaboradorController::class, 'editAction'])->name('Job.editar_action');
    Route::post('/dashboard/copiar', [ColaboradorController::class, 'copyAction'])->name('Job.copiar_action');
    Route::post('/reaberto/{id}', [ColaboradorController::class, 'reOpenJob'])->name('reaberto');
    Route::get('/prioridade', [ColaboradorController::class, 'changeCategory'])->name('prioridade');
    Route::post('/finalizar/demanda/{id}', [ColaboradorController::class, 'finalize'])->name('Finalizar_action');
    Route::post('/pausar/demanda/{id}', [ColaboradorController::class, 'pause'])->name('Pausar_action');
    Route::post('/retomar/demanda/{id}', [ColaboradorController::class, 'resume'])->name('Retomar_action');
    Route::post('/jobs/date', [ColaboradorController::class, 'getJobsByDate'])->name('Job.date');
    Route::post('/pauta/aceitar/tempo/colaborador/{id}', [ColaboradorController::class, 'acceptTime'])->name('Pauta.Aceitar_tempo_colaborador');
    Route::post('/receber/alteracoes/{id}', [ColaboradorController::class, 'receiveAlteration'])->name('Receber_alteracoes');
    // Route::post('/usuarios/busca', [ColaboradorController::class, 'getUserAgency'])->name('Usuario.busca');
    Route::post('/respostas/create/{id}', [RespostasController::class, 'answerCreate'])->name('Answer.create');
    Route::post('/respostas/action/{id}', [RespostasController::class, 'answerAction'])->name('Answer.action');
    Route::post('/respostas/delete/{id}', [RespostasController::class, 'delete'])->name('Answer.delete');
    Route::get('/respostas/editar/{id}', [RespostasController::class, 'getAnswer'])->name('getAnswer');
    Route::post('/respostas/editar-form', [RespostasController::class, 'getAnswerAction'])->name('Answer.edit');

    Route::post('/recorrencia/{id}', [RecorrenciasController::class, 'campanhaRecorrenciaAction'])->name('Campanha.recorrencia');
    Route::post('/recorrencia/mensal/editar/{id}', [RecorrenciasController::class, 'getRecorrenciaMensalAction'])->name('Recorrencia.mensal_action');
    Route::post('/recorrencia/mensal/criar/{id}', [RecorrenciasController::class, 'getRecorrenciaMensalCreateAction'])->name('Recorrencia.mensal_create_action');
    Route::post('/recorrencia/anual/editar/{id}', [RecorrenciasController::class, 'getRecorrenciaAnualAction'])->name('Recorrencia.anual_action');
    Route::post('/recorrencia/anual/criar/{id}', [RecorrenciasController::class, 'getRecorrenciaAnualCreateAction'])->name('Recorrencia.anual_create_action');
    Route::post('/recorrencia/semanal/editar/{id}', [RecorrenciasController::class, 'getRecorrenciaSemanalAction'])->name('Recorrencia.semanal_action');
    Route::post('/recorrencia/semanal/criar/{id}', [RecorrenciasController::class, 'getRecorrenciaSemanalCreateAction'])->name('Recorrencia.semanal_create_action');
    Route::delete('/campanha/excluir/{id}', [RecorrenciasController::class, 'recorrenciaDelete'])->name('Recorrencia.delete');
    Route::delete('/recorrencia/excluir/{id}', [RecorrenciasController::class, 'recorrenciaSingleDelete'])->name('Recorrencia.single_delete');

    Route::post('/ajuste/recorrencia/criar/', [RecorrenciasController::class, 'getRecorrenciaAjusteCreateAction'])->name('Recorrencia.ajuste_create_action');
    Route::post('/ajuste/recorrencia/editar/{id}', [RecorrenciasController::class, 'getRecorrenciaAjusteEditAction'])->name('Recorrencia.ajuste_edit_action');
    Route::delete('/ajuste/recorrencia/excluir/{id}', [RecorrenciasController::class, 'recorrenciaAjusteDelete'])->name('Recorrencia.ajuste_delete');
    Route::get('/ajuste/get/editar/{id}', [RecorrenciasController::class, 'getRecorrenciaSingleAjusteAction'])->name('Recorrencia.ajuste_single_edit_action');



});

//agencia admin
Route::middleware(['auth', 'isAdminAgencia'])->group(function(){
    Route::get('/agencia/criar/etapa/1', [AdminAgenciaController::class, 'create'])->name('Agencia.criar');
    Route::get('/agencia/criar/job/{id}/etapa/2', [AdminAgenciaController::class, 'createStage2'])->name('Agencia.criar_etapa_2');
    Route::get('/agencia/jobs', [AdminAgenciaController::class, 'jobs'])->name('Agencia.Jobs');
    Route::get('/agencia/etapas', [AdminAgenciaController::class, 'stages'])->name('Agencia.Etapas');
    Route::get('/agencia/deletar/job/{id}/etapa/1', [AdminAgenciaController::class, 'deleteStage1'])->name('Agencia.deletar_etapa_1');
    Route::get('/agencia/job/editar/{id}', [AdminAgenciaController::class, 'edit'])->name('Agencia.editar');
    Route::get('/agencia/job/copiar/{id}', [AdminAgenciaController::class, 'copy'])->name('Agencia.copiar');
    Route::get('/agencia/delete/{id}', [AdminAgenciaController::class, 'delete'])->name('Agencia.delete');
    Route::post('/agencia/criar-action', [AdminAgenciaController::class, 'createAction'])->name('Agencia.criar_action');
    Route::post('/agencia/job/{id}/criar-action-etapa-2', [AdminAgenciaController::class, 'createActionStage2'])->name('Agencia.criar_action_stage_2');
    Route::post('/agencia/editar/{id}', [AdminAgenciaController::class, 'editAction'])->name('Agencia.editar_action');
    Route::post('/agencia/copiar', [AdminAgenciaController::class, 'copyAction'])->name('Agencia.copiar_action');
    Route::get('/getBrandsColaborador/{id}', [AdminAgenciaController::class, 'getBrandsColaborador'])->name('getBrands');
    Route::post('/usuarios/busca', [AdminAgenciaController::class, 'getUserAgency'])->name('Usuario.busca');

    // Route::post('/agencia/reaberto/{id}', [AdminAgenciaController::class, 'reOpenJob'])->name('Agencia.reaberto');
    // Route::get('/agencia/prioridade', [AdminAgenciaController::class, 'changeCategory'])->name('Agencia;prioridade');
    // Route::post('/agencia/finalizar/demanda/{id}', [AdminAgenciaController::class, 'finalize'])->name('Agencia.Finalizar_action');
    // Route::post('/agencia/pausar/demanda/{id}', [AdminAgenciaController::class, 'pause'])->name('Agencia.Pausar_action');
    // Route::post('/agencia/retomar/demanda/{id}', [AdminAgenciaController::class, 'resume'])->name('Agencia.Retomar_action');
    // Route::post('/agencia/jobs/date', [AdminAgenciaController::class, 'getJobsByDate'])->name('Job.date');
    // Route::post('/agencia/pauta/aceitar/tempo/colaborador/{id}', [AdminAgenciaController::class, 'acceptTime'])->name('Agencia.Aceitar_tempo_colaborador');
    // Route::post('/agencia/receber/alteracoes/{id}', [AdminAgenciaController::class, 'receiveAlteration'])->name('Agencia.Receber_alteracoes');
});

//logado
Route::middleware(['auth'])->group(function(){
    Route::get('/meu-perfil', [UsuariosController::class, 'index'])->name('Usuario');
    Route::get('/estados/{id}', [UsuariosController::class, 'getCityByStates'])->name('getEstados');
    Route::post('/usuario/{id}', [UsuariosController::class, 'edit'])->name('Usuario.editar_action');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('image-download/{id}', [DemandasController::class, 'downloadImage'])->name('download.image');
    Route::post('/imagem/delete/{id}', [DemandasController::class, 'deleteArq'])->name('Imagem.delete');
    Route::post('/comentario/action/{id}', [ComentariosController::class, 'comentaryAction'])->name('Comentario.action');
    Route::post('/comentario/delete/{id}', [ComentariosController::class, 'delete'])->name('Comentario.delete');
    Route::get('/comentario/editar/{id}', [ComentariosController::class, 'getComentary'])->name('getComentary');
    Route::post('/comentario/editar-form', [ComentariosController::class, 'getComentaryAction'])->name('Comentario.edit');
    Route::get('/job/{id}', [DemandasController::class, 'index'])->name('Job');
    Route::post('/imagem/upload/{id}', [DemandasController::class, 'uploadImg'])->name('Imagem.upload');
    Route::get('/notificacoes', [NotificacoesController::class, 'index'])->name('Notification');
    Route::post('/notificacao/action', [NotificacoesController::class, 'action'])->name('Notification.action');
    Route::post('/notificacao/{id}', [NotificacoesController::class, 'actionSingle'])->name('Notification.action.single');
    Route::get('/notificacao/ler-todas', [NotificacoesController::class, 'readAll'])->name('Notification.read.all');
    Route::post('/demanda/prazo/sugerido/{id}', [DemandasController::class, 'changeTime'])->name('Demanda.prazo.action');
    Route::get('/agencia/job/{id}', [AdminAgenciaController::class, 'job'])->name('Agencia.Job');
    Route::post('/job/ordem', [UsuariosController::class, 'ordem'])->name('Job.ordem');
    Route::post('/demandas/ordem', [UsuariosController::class, 'ordemDemandas'])->name('Demandas.ordem');
    Route::match(['get', 'post'], '/atualizacoes/criar', [AtualizacoesController::class, 'criar'])->name('Atualizacoes.criar');
    Route::get('/atualizacoes', [AtualizacoesController::class, 'index'])->name('Atualizacoes.index');
    Route::post('/upload-imagem', [AtualizacoesController::class, 'uploadImage']);
    Route::get('/atualizacoes/excluir/{id}', [AtualizacoesController::class, 'excluir'])->name('Atualizacoes.excluir');
    Route::post('/adicionar/usuario/job', [DemandasController::class, 'addUser'])->name('Adicionar.usuario');
    Route::post('/adicionar/colaborador/job', [DemandasController::class, 'addCol'])->name('Adicionar.colaborador');
    Route::match(['get', 'post'], '/atualizacoes-editar/{id}', [AtualizacoesController::class, 'editar'])->name('Atualizacoes.editar');
    Route::post('/ler/briefing/job', [DemandasController::class, 'readBriefing'])->name('Ler.briefing');


    Route::post('/comentario/recorrencia/criar/', [RecorrenciasController::class, 'recorrenciaComentarioCreate'])->name('Recorrencia.comentario_create_action');
    Route::post('/comentario/recorrencia/editar/{id}', [RecorrenciasController::class, 'recorrenciaComentarioEdit'])->name('Recorrencia.comentario_edit_action');
    Route::get('/comentario/recorrencia/get/editar/{id}', [RecorrenciasController::class, 'recorrenciaComentarioGetEdit'])->name('Recorrencia.comentario_edit_get');
    Route::delete('/comentario/recorrencia/excluir/{id}', [RecorrenciasController::class, 'recorrenciaComentarioDelete'])->name('Recorrencia.comentario_delete');
    Route::post('/comentario/recorrencia/ler/{id}', [RecorrenciasController::class, 'recorrenciaComentarioRead'])->name('Recorrencia.comentario_read');
    Route::post('/recorrencia/pauta/iniciar/{id}', [RecorrenciasController::class, 'startRecorrencia'])->name('Recorrencia.Pauta.iniciar_tempo');
    Route::post('/recorrencia/pauta/entregar/{id}', [RecorrenciasController::class, 'deliverRecorrencia'])->name('Recorrencia.Pauta.entregar_tempo');
    Route::post('/recorrencia/pauta/finalizar/{id}', [RecorrenciasController::class, 'finalizeRecorrencia'])->name('Recorrencia.Pauta.finalizar_tempo');
    Route::post('/recorrencia/data/{id}', [RecorrenciasController::class, 'newDate'])->name('Recorrencia.data');
    Route::post('/recorrencia/ajuste/pauta/iniciar/{id}', [RecorrenciasController::class, 'startRecorrenciaAjuste'])->name('Recorrencia.Ajuste.Pauta.entregar_tempo');
    Route::post('/recorrencia/ajuste/pauta/finalizar/{id}', [RecorrenciasController::class, 'DeliverRecorrenciaAjuste'])->name('Recorrencia.Ajuste.Pauta.finalizar_tempo');
    Route::post('/check/recorrencia', [RecorrenciasController::class, 'recorrenciaCheck'])->name('Recorrencia.Check');
    Route::post('/check/ajuste/recorrencia', [RecorrenciasController::class, 'recorrenciaAjusteCheck'])->name('Recorrencia.Ajuste_check');
    Route::post('/ajuste/data/recorrencia/{id}', [RecorrenciasController::class, 'newDateAjuste'])->name('Recorrencia.ajuste_data');
  
});

//login
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'login_action'])->name('login_action');
Route::get('/recuperar/senha', [UsuariosController::class, 'forgotPassword'])->name('forgotPassword');
Route::post('/recuperar/senha', [UsuariosController::class, 'forgotPasswordAction'])->name('forgotPassword.action');
Route::get('/recuperar/senha/{token}', [UsuariosController::class, 'showResetForm'])->name('ShowResetForm');
Route::post('/resetar/senha', [UsuariosController::class, 'resetpassword'])->name('Resetpassword');


