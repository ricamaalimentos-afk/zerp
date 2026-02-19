<?php
$pdo = obterConexao();
$titulos = $pdo->query('SELECT cr.id, cr.venda_id, cr.vencimento, cr.valor_parcela, cr.valor_aberto, cr.status FROM contas_receber cr ORDER BY cr.id DESC')->fetchAll();
?>
<h1 class="h3 mb-3 text-gray-800">Contas a Receber</h1>
<div class="card"><div class="card-body"><table class="table table-striped datatable"><thead><tr><th>Título</th><th>Venda</th><th>Vencimento</th><th>Parcela</th><th>Aberto</th><th>Status</th></tr></thead><tbody>
<?php foreach($titulos as $t): ?><tr><td><?= $t['id']; ?></td><td><?= $t['venda_id']; ?></td><td><?= dataBr($t['vencimento']); ?></td><td><?= moedaBr((float)$t['valor_parcela']); ?></td><td><?= moedaBr((float)$t['valor_aberto']); ?></td><td><?= sanitizar($t['status']); ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
