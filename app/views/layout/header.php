<?php $usuarioSessao = $_SESSION['usuario'] ?? null; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($config['app']['nome_sistema']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="<?= $config['app']['url_base'] ?>/../assets/css/estilo.css">
</head>
<body id="page-top">
<div id="wrapper">
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= $config['app']['url_base'] ?>/">
        <div class="sidebar-brand-text mx-3">ERP Zerp</div>
    </a>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/"><i class="fas fa-fw fa-home"></i><span>Dashboard</span></a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/cadastros/usuarios">Usuários</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/cadastros/clientes">Clientes</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/cadastros/produtos">Produtos</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/cadastros/grupos-clientes">Grupo Clientes</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/cadastros/formas-pagamento">Formas de Pagamento</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/cadastros/tabelas-preco">Tabelas de Preço</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/vendas">Pedidos/Vendas</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/contas-receber">Contas a Receber</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= $config['app']['url_base'] ?>/cadastros/configuracoes">Configurações</a></li>
</ul>
<div id="content-wrapper" class="d-flex flex-column"><div id="content">
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
<ul class="navbar-nav ml-auto"><li class="nav-item mr-3 mt-2"><?= htmlspecialchars($usuarioSessao['nome'] ?? '') ?></li><li class="nav-item"><a class="btn btn-sm btn-danger" href="<?= $config['app']['url_base'] ?>/sair">Sair</a></li></ul>
</nav>
<div class="container-fluid">
