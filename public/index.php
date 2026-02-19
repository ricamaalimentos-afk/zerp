<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/auth.php';

$rota = $_GET['rota'] ?? 'dashboard';

if ($rota === 'logout') {
    session_destroy();
    redirecionar('login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/salvar.php';
}

if ($rota === 'login') {
    include __DIR__ . '/../app/pages/login.php';
    exit;
}

exigirLogin();

include __DIR__ . '/../app/layout/header.php';

$rotasPermitidas = [
    'dashboard' => 'dashboard.php',
    'usuarios' => 'usuarios.php',
    'clientes' => 'clientes.php',
    'produtos' => 'produtos.php',
    'vendas' => 'vendas.php',
    'receber' => 'receber.php',
    'configuracoes' => 'configuracoes.php',
];

$arquivo = $rotasPermitidas[$rota] ?? 'dashboard.php';
include __DIR__ . '/../app/pages/' . $arquivo;

include __DIR__ . '/../app/layout/footer.php';
