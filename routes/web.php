<?php

declare(strict_types=1);

use App\Controllers\ArquivoController;
use App\Controllers\AuditoriaController;
use App\Controllers\AuthController;
use App\Controllers\ConviteController;
use App\Controllers\CategoriaController;
use App\Controllers\ContaController;
use App\Controllers\DashboardController;
use App\Controllers\EmpresaController;
use App\Controllers\EquipeController;
use App\Controllers\HealthController;
use App\Controllers\LandingController;
use App\Controllers\LancamentoController;
use App\Controllers\LgpdController;
use App\Controllers\ApiTokenController;
use App\Controllers\CentroCustoController;
use App\Controllers\ContatoController;
use App\Controllers\IntegracaoController;
use App\Controllers\MetaController;
use App\Controllers\NotificacaoController;
use App\Controllers\OrcamentoController;
use App\Controllers\PerfilController;
use App\Controllers\RelatorioController;
use App\Controllers\ContaPagarController;
use App\Controllers\ContaReceberController;
use App\Controllers\CobrancaController;
use App\Controllers\NotaFiscalController;
use App\Controllers\AutomacaoController;
use App\Controllers\ConciliacaoController;
use App\Controllers\AssistenteController;
use App\Controllers\WebhookController;
use App\Controllers\SuperAdminController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\EmpresaMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PlanMiddleware;
use App\Middleware\RbacMiddleware;
use App\Middleware\SuperAdminMiddleware;

/** @var Router $router */
$wrap = fn (array $h): callable => fn (...$p) => (new $h[0]())->{$h[1]}(...$p);

$auth = new AuthMiddleware();
$guest = new GuestMiddleware();
$csrf = new CsrfMiddleware();
$empresa = new EmpresaMiddleware();
$rbac = new RbacMiddleware('config');
$planEmpresa = new PlanMiddleware('empresa');
$planConvite = new PlanMiddleware('convite');
$superadmin = new SuperAdminMiddleware();

$router->get('/health', $wrap([HealthController::class, 'check']));

$router->get('/login', $wrap([AuthController::class, 'loginForm']), [$guest]);
$router->post('/login', $wrap([AuthController::class, 'login']), [$guest, $csrf]);
$router->get('/cadastro', $wrap([AuthController::class, 'registerForm']), [$guest]);
$router->post('/cadastro', $wrap([AuthController::class, 'register']), [$guest, $csrf]);
$router->get('/auth/confirmacao', $wrap([AuthController::class, 'confirmarEmail']));
$router->get('/recuperar', $wrap([AuthController::class, 'recuperarForm']), [$guest]);
$router->post('/recuperar', $wrap([AuthController::class, 'recuperar']), [$guest, $csrf]);
$router->get('/redefinir', $wrap([AuthController::class, 'redefinirForm']), [$guest]);
$router->post('/redefinir', $wrap([AuthController::class, 'redefinir']), [$guest, $csrf]);
$router->post('/logout', $wrap([AuthController::class, 'logout']), [$auth, $csrf]);

$router->get('/privacidade', $wrap([LgpdController::class, 'privacidade']));
$router->get('/termos', $wrap([LgpdController::class, 'termos']));
$router->get('/convite/{token}', $wrap([ConviteController::class, 'aceitarForm']), [$guest]);
$router->post('/convite/{token}', $wrap([ConviteController::class, 'aceitar']), [$guest, $csrf]);

$router->get('/privacidade/meus-dados', $wrap([LgpdController::class, 'meusDados']), [$auth]);
$router->get('/privacidade/exportar', $wrap([LgpdController::class, 'exportar']), [$auth]);
$router->get('/privacidade/exportar-empresa', $wrap([LgpdController::class, 'exportarEmpresa']), [$auth, $empresa, $rbac]);
$router->post('/privacidade/retificar', $wrap([LgpdController::class, 'retificar']), [$auth, $csrf]);
$router->post('/privacidade/excluir', $wrap([LgpdController::class, 'solicitarExclusao']), [$auth, $csrf]);
$router->post('/privacidade/confirmar-exclusao', $wrap([LgpdController::class, 'confirmarExclusao']), [$auth, $csrf]);
$router->post('/privacidade/cookies', $wrap([LgpdController::class, 'aceitarCookies']), [$csrf]);
$router->get('/arquivo', $wrap([ArquivoController::class, 'download']), [$auth, $empresa]);

$router->get('/', $wrap([LandingController::class, 'index']));
$router->get('/dashboard', $wrap([DashboardController::class, 'index']), [$auth, $empresa]);
$router->post('/onboarding/concluir', $wrap([DashboardController::class, 'concluirOnboarding']), [$auth, $csrf]);

$router->get('/perfil', $wrap([PerfilController::class, 'index']), [$auth]);
$router->post('/perfil', $wrap([PerfilController::class, 'atualizar']), [$auth, $csrf]);
$router->post('/perfil/senha', $wrap([PerfilController::class, 'senha']), [$auth, $csrf]);

$router->get('/superadmin', $wrap([SuperAdminController::class, 'index']), [$auth, $superadmin]);
$router->get('/superadmin/usuarios', $wrap([SuperAdminController::class, 'usuarios']), [$auth, $superadmin]);
$router->get('/superadmin/empresas', $wrap([SuperAdminController::class, 'empresas']), [$auth, $superadmin]);
$router->get('/superadmin/logins', $wrap([SuperAdminController::class, 'logins']), [$auth, $superadmin]);
$router->post('/superadmin/promover', $wrap([SuperAdminController::class, 'promover']), [$auth, $superadmin, $csrf]);
$router->post('/superadmin/revogar', $wrap([SuperAdminController::class, 'revogar']), [$auth, $superadmin, $csrf]);

$router->get('/notificacoes', $wrap([NotificacaoController::class, 'index']), [$auth]);
$router->post('/notificacoes/{id}/lida', $wrap([NotificacaoController::class, 'marcarLida']), [$auth, $csrf]);
$router->post('/notificacoes/lidas', $wrap([NotificacaoController::class, 'marcarTodas']), [$auth, $csrf]);

$router->get('/webhooks', $wrap([WebhookController::class, 'index']), [$auth, $empresa, $rbac]);
$router->post('/webhooks', $wrap([WebhookController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/webhooks/{id}/excluir', $wrap([WebhookController::class, 'excluir']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/api/tokens', $wrap([ApiTokenController::class, 'index']), [$auth, $empresa, $rbac]);
$router->post('/api/tokens', $wrap([ApiTokenController::class, 'criar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/api/tokens/{id}/revogar', $wrap([ApiTokenController::class, 'revogar']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/orcamentos', $wrap([OrcamentoController::class, 'index']), [$auth, $empresa]);
$router->post('/orcamentos', $wrap([OrcamentoController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/orcamentos/{id}/excluir', $wrap([OrcamentoController::class, 'excluir']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/centros-custo', $wrap([CentroCustoController::class, 'index']), [$auth, $empresa, $rbac]);
$router->post('/centros-custo', $wrap([CentroCustoController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/centros-custo/{id}/excluir', $wrap([CentroCustoController::class, 'excluir']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/contatos', $wrap([ContatoController::class, 'index']), [$auth, $empresa, $rbac]);
$router->post('/contatos', $wrap([ContatoController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/contatos/{id}/excluir', $wrap([ContatoController::class, 'excluir']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/integracoes', $wrap([IntegracaoController::class, 'index']), [$auth, $empresa, $rbac]);
$router->post('/integracoes', $wrap([IntegracaoController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/empresas', $wrap([EmpresaController::class, 'index']), [$auth]);
$router->get('/empresas/criar', $wrap([EmpresaController::class, 'criarForm']), [$auth]);
$router->post('/empresas', $wrap([EmpresaController::class, 'criar']), [$auth, $planEmpresa, $csrf]);
$router->get('/empresas/{id}/editar', $wrap([EmpresaController::class, 'editarForm']), [$auth]);
$router->post('/empresas/{id}', $wrap([EmpresaController::class, 'editar']), [$auth, $csrf]);
$router->post('/empresas/{id}/trocar', $wrap([EmpresaController::class, 'trocar']), [$auth, $csrf]);
$router->post('/empresas/{id}/convidar', $wrap([EmpresaController::class, 'convidar']), [$auth, $rbac, $planConvite, $csrf]);

$router->get('/equipe', $wrap([EquipeController::class, 'index']), [$auth, $empresa, $rbac]);
$router->post('/equipe/{id}/remover', $wrap([EquipeController::class, 'remover']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/equipe/{id}/papel', $wrap([EquipeController::class, 'alterarPapel']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/equipe/convites/{id}/cancelar', $wrap([EquipeController::class, 'cancelarConvite']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/auditoria', $wrap([AuditoriaController::class, 'index']), [$auth, $empresa, $rbac]);

$router->get('/contas', $wrap([ContaController::class, 'index']), [$auth, $empresa]);
$router->get('/contas/criar', $wrap([ContaController::class, 'criarForm']), [$auth, $empresa, $rbac]);
$router->post('/contas', $wrap([ContaController::class, 'criar']), [$auth, $empresa, $rbac, $csrf]);
$router->get('/contas/{id}/editar', $wrap([ContaController::class, 'editarForm']), [$auth, $empresa, $rbac]);
$router->post('/contas/{id}', $wrap([ContaController::class, 'editar']), [$auth, $empresa, $rbac, $csrf]);
$router->get('/contas/{id}/extrato', $wrap([ContaController::class, 'extrato']), [$auth, $empresa]);
$router->get('/contas/transferir', $wrap([ContaController::class, 'transferirForm']), [$auth, $empresa]);
$router->post('/contas/transferir', $wrap([ContaController::class, 'transferir']), [$auth, $empresa, $csrf]);

$router->get('/categorias', $wrap([CategoriaController::class, 'index']), [$auth, $empresa]);
$router->post('/categorias', $wrap([CategoriaController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/categorias/{id}/excluir', $wrap([CategoriaController::class, 'excluir']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/contas-a-pagar', $wrap([ContaPagarController::class, 'index']), [$auth, $empresa]);
$router->post('/contas-a-pagar/pagar-lote', $wrap([ContaPagarController::class, 'pagarLote']), [$auth, $empresa, $csrf]);
$router->get('/contas-a-receber', $wrap([ContaReceberController::class, 'index']), [$auth, $empresa]);
$router->post('/contas-a-receber/receber-lote', $wrap([ContaReceberController::class, 'receberLote']), [$auth, $empresa, $csrf]);

$router->get('/cobrancas', $wrap([CobrancaController::class, 'index']), [$auth, $empresa]);
$router->get('/cobrancas/criar', $wrap([CobrancaController::class, 'criarForm']), [$auth, $empresa]);
$router->get('/cobrancas/{id}', $wrap([CobrancaController::class, 'ver']), [$auth, $empresa]);
$router->get('/cobrancas/{id}/editar', $wrap([CobrancaController::class, 'editarForm']), [$auth, $empresa]);
$router->post('/cobrancas', $wrap([CobrancaController::class, 'salvar']), [$auth, $empresa, $csrf]);
$router->post('/cobrancas/{id}/emitir', $wrap([CobrancaController::class, 'emitir']), [$auth, $empresa, $csrf]);
$router->post('/cobrancas/{id}/pagar', $wrap([CobrancaController::class, 'marcarPaga']), [$auth, $empresa, $csrf]);
$router->post('/cobrancas/{id}/cancelar', $wrap([CobrancaController::class, 'cancelar']), [$auth, $empresa, $csrf]);
$router->post('/cobrancas/{id}/enviar-email', $wrap([CobrancaController::class, 'enviarEmail']), [$auth, $empresa, $csrf]);

$router->get('/notas-fiscais', $wrap([NotaFiscalController::class, 'index']), [$auth, $empresa]);
$router->get('/notas-fiscais/criar', $wrap([NotaFiscalController::class, 'criarForm']), [$auth, $empresa]);
$router->get('/notas-fiscais/{id}', $wrap([NotaFiscalController::class, 'ver']), [$auth, $empresa]);
$router->get('/notas-fiscais/{id}/editar', $wrap([NotaFiscalController::class, 'editarForm']), [$auth, $empresa]);
$router->post('/notas-fiscais', $wrap([NotaFiscalController::class, 'salvar']), [$auth, $empresa, $csrf]);
$router->post('/notas-fiscais/{id}/emitir', $wrap([NotaFiscalController::class, 'emitir']), [$auth, $empresa, $csrf]);

$router->get('/automacoes', $wrap([AutomacaoController::class, 'index']), [$auth, $empresa, $rbac]);
$router->post('/automacoes', $wrap([AutomacaoController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/automacoes/{id}/toggle', $wrap([AutomacaoController::class, 'toggle']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/automacoes/{id}/excluir', $wrap([AutomacaoController::class, 'excluir']), [$auth, $empresa, $rbac, $csrf]);

$router->get('/conciliacoes', $wrap([ConciliacaoController::class, 'index']), [$auth, $empresa]);
$router->post('/conciliacoes/importar', $wrap([ConciliacaoController::class, 'importar']), [$auth, $empresa, $csrf]);
$router->get('/conciliacoes/{id}', $wrap([ConciliacaoController::class, 'ver']), [$auth, $empresa]);
$router->post('/conciliacoes/{id}/conciliar', $wrap([ConciliacaoController::class, 'conciliar']), [$auth, $empresa, $csrf]);
$router->post('/conciliacoes/{id}/ignorar', $wrap([ConciliacaoController::class, 'ignorar']), [$auth, $empresa, $csrf]);
$router->post('/conciliacoes/{id}/criar-lancamento', $wrap([ConciliacaoController::class, 'criarLancamento']), [$auth, $empresa, $csrf]);

$router->get('/assistente', $wrap([AssistenteController::class, 'index']), [$auth, $empresa]);
$router->post('/assistente/perguntar', $wrap([AssistenteController::class, 'perguntar']), [$auth, $empresa, $csrf]);

$router->get('/lancamentos', $wrap([LancamentoController::class, 'index']), [$auth, $empresa]);
$router->get('/lancamentos/criar', $wrap([LancamentoController::class, 'criarForm']), [$auth, $empresa]);
$router->get('/lancamentos/{id}/editar', $wrap([LancamentoController::class, 'editarForm']), [$auth, $empresa]);
$router->post('/lancamentos', $wrap([LancamentoController::class, 'salvar']), [$auth, $empresa, $csrf]);
$router->post('/lancamentos/{id}/status', $wrap([LancamentoController::class, 'toggleStatus']), [$auth, $empresa, $csrf]);
$router->post('/lancamentos/{id}/duplicar', $wrap([LancamentoController::class, 'duplicar']), [$auth, $empresa, $csrf]);
$router->post('/lancamentos/{id}/excluir', $wrap([LancamentoController::class, 'excluir']), [$auth, $empresa, $csrf]);
$router->get('/lancamentos/importar', $wrap([LancamentoController::class, 'importarForm']), [$auth, $empresa, $rbac]);
$router->post('/lancamentos/importar/preview', $wrap([LancamentoController::class, 'previewImport']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/lancamentos/importar', $wrap([LancamentoController::class, 'importar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/lancamentos/{id}/aprovar', $wrap([LancamentoController::class, 'aprovar']), [$auth, $empresa, $rbac, $csrf]);
$router->get('/lancamentos/exportar', $wrap([LancamentoController::class, 'exportarCsv']), [$auth, $empresa]);
$router->get('/lancamentos/template-csv', $wrap([LancamentoController::class, 'templateCsv']), [$auth, $empresa]);

$router->get('/relatorios/dre', $wrap([RelatorioController::class, 'dre']), [$auth, $empresa]);
$router->get('/relatorios/fluxo', $wrap([RelatorioController::class, 'fluxo']), [$auth, $empresa]);
$router->get('/relatorios/categoria', $wrap([RelatorioController::class, 'categoria']), [$auth, $empresa]);
$router->get('/relatorios/centro-custo', $wrap([RelatorioController::class, 'centroCusto']), [$auth, $empresa]);

$router->get('/metas', $wrap([MetaController::class, 'index']), [$auth, $empresa]);
$router->post('/metas', $wrap([MetaController::class, 'salvar']), [$auth, $empresa, $rbac, $csrf]);
$router->post('/metas/{id}/excluir', $wrap([MetaController::class, 'excluir']), [$auth, $empresa, $rbac, $csrf]);
