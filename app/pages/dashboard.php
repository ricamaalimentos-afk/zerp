<?php
$pdo = obterConexao();
$totais = [
    'clientes' => (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
    'produtos' => (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn(),
    'vendas' => (int) $pdo->query('SELECT COUNT(*) FROM vendas')->fetchColumn(),
    'receber' => (float) $pdo->query('SELECT COALESCE(SUM(valor_aberto),0) FROM contas_receber WHERE status <> "BAIXADO"')->fetchColumn(),
];
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
</div>
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">Clientes: <strong><?= $totais['clientes']; ?></strong></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">Produtos: <strong><?= $totais['produtos']; ?></strong></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-info shadow h-100 py-2"><div class="card-body">Vendas: <strong><?= $totais['vendas']; ?></strong></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body">A receber: <strong><?= moedaBr($totais['receber']); ?></strong></div></div></div>
</div>
