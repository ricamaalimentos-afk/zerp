<?php
$pdo = obterConexao();
$vendas = $pdo->query('SELECT v.id, v.data_venda, c.nome_razao AS cliente, u.nome AS vendedor, v.total_liquido, v.status FROM vendas v LEFT JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.usuario_id ORDER BY v.id DESC')->fetchAll();
?>
<h1 class="h3 mb-3 text-gray-800">Pedidos / Vendas</h1>
<div class="alert alert-info">Regra de preço implementada no desenho de dados: Cliente &gt; Grupo de cliente &gt; Produto padrão. Próxima etapa: tela de inclusão de itens e faturamento completo.</div>
<div class="card"><div class="card-body"><table class="table table-striped datatable"><thead><tr><th>Nº</th><th>Data</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Status</th></tr></thead><tbody>
<?php foreach($vendas as $v): ?><tr><td><?= $v['id']; ?></td><td><?= dataBr($v['data_venda']); ?></td><td><?= sanitizar((string)$v['cliente']); ?></td><td><?= sanitizar((string)$v['vendedor']); ?></td><td><?= moedaBr((float)$v['total_liquido']); ?></td><td><?= sanitizar($v['status']); ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
