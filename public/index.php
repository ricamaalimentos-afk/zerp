<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CadastroController;
use App\Controllers\ContaReceberController;
use App\Controllers\DashboardController;
use App\Controllers\VendaController;
use App\Core\BancoDados;

session_start();

spl_autoload_register(function ($classe) {
    $prefixo = 'App\\';
    if (strpos($classe, $prefixo) !== 0) {
        return;
    }
    $caminho = __DIR__ . '/../app/' . str_replace('App\\', '', $classe) . '.php';
    $caminho = str_replace('\\', '/', $caminho);
    if (file_exists($caminho)) {
        require $caminho;
    }
});

$config = require __DIR__ . '/../app/config/config.php';
date_default_timezone_set($config['app']['fuso_horario']);
$banco = BancoDados::conectar($config['banco']);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = parse_url($config['app']['url_base'], PHP_URL_PATH);
$caminho = '/' . trim(str_replace($base, '', $uri), '/');
$caminho = $caminho === '/' ? '/' : rtrim($caminho, '/');
$metodo = $_SERVER['REQUEST_METHOD'];

$auth = new AuthController($config, $banco);
$dashboard = new DashboardController($config, $banco);
$cadastro = new CadastroController($config, $banco);
$venda = new VendaController($config, $banco);
$conta = new ContaReceberController($config, $banco);

if ($caminho === '/login' && $metodo === 'GET') { $auth->loginFormulario(); exit; }
if ($caminho === '/login' && $metodo === 'POST') { $auth->loginSalvar(); exit; }
if ($caminho === '/sair') { $auth->sair(); exit; }
if ($caminho === '/') { $dashboard->index(); exit; }
if ($caminho === '/vendas') {
    if ($metodo === 'POST') { $venda->salvar(); } else { $venda->index(); }
    exit;
}
if (preg_match('#^/vendas/finalizar/(\d+)$#', $caminho, $m)) { $venda->finalizar((int)$m[1]); exit; }
if ($caminho === '/contas-receber') { $conta->index(); exit; }
if (preg_match('#^/contas-receber/baixar/(\d+)$#', $caminho, $m)) { $conta->baixar((int)$m[1]); exit; }
if (preg_match('#^/contas-receber/reabrir/(\d+)$#', $caminho, $m)) { $conta->reabrir((int)$m[1]); exit; }
if (preg_match('#^/cadastros/([a-z\-]+)$#', $caminho, $m)) {
    if ($metodo === 'POST') { $cadastro->salvar($m[1]); } else { $cadastro->index($m[1]); }
    exit;
}
if (preg_match('#^/cadastros/([a-z\-]+)/excluir/(\d+)$#', $caminho, $m)) { $cadastro->excluir($m[1], (int)$m[2]); exit; }

http_response_code(404);
echo 'Rota não encontrada.';
