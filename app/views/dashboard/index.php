<div class="d-sm-flex align-items-center justify-content-between mb-4"><h1 class="h3 mb-0 text-gray-800">Dashboard</h1></div>
<div class="row">
<div class="col-md-3"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">Clientes: <?= $totais['clientes'] ?></div></div></div>
<div class="col-md-3"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">Produtos: <?= $totais['produtos'] ?></div></div></div>
<div class="col-md-3"><div class="card border-left-info shadow h-100 py-2"><div class="card-body">Vendas finalizadas: <?= $totais['vendas'] ?></div></div></div>
<div class="col-md-3"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body">A receber: R$ <?= number_format($totais['receber_aberto'], 2, ',', '.') ?></div></div></div>
</div>
