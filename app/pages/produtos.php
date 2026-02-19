<?php
$pdo = obterConexao();
$produtos = $pdo->query('SELECT codigo, descricao, unidade_medida, ncm, preco_venda, ativo FROM produtos ORDER BY id DESC')->fetchAll();
?>
<h1 class="h3 mb-3 text-gray-800">Cadastro de Produtos</h1>
<div class="card mb-4"><div class="card-body">
<form method="post">
<input type="hidden" name="acao" value="salvar_produto">
<div class="form-row">
<div class="form-group col-md-2"><label>Código</label><input class="form-control" name="codigo" value="P<?= time(); ?>" required></div>
<div class="form-group col-md-5"><label>Descrição</label><input class="form-control upper" name="descricao" required></div>
<div class="form-group col-md-3"><label>Descrição resumida</label><input class="form-control upper" name="descricao_resumida"></div>
<div class="form-group col-md-2"><label>Unidade</label><input class="form-control" name="unidade_medida" value="UN"></div>
</div>
<div class="form-row">
<div class="form-group col-md-2"><label>Marca</label><input class="form-control upper" name="marca"></div>
<div class="form-group col-md-2"><label>Cód. Barras</label><input class="form-control" name="codigo_barras"></div>
<div class="form-group col-md-1"><label>CST Ent</label><input class="form-control" name="cst_entrada"></div>
<div class="form-group col-md-1"><label>CST NF-e</label><input class="form-control" name="cst_nfe"></div>
<div class="form-group col-md-1"><label>CST NFC-e</label><input class="form-control" name="cst_nfce"></div>
<div class="form-group col-md-1"><label>CSOSN</label><input class="form-control" name="csosn"></div>
<div class="form-group col-md-2"><label>NCM</label><input class="form-control" name="ncm"></div>
<div class="form-group col-md-2"><label>CEST</label><input class="form-control" name="cest"></div>
</div>
<div class="form-row">
<div class="form-group col-md-2"><label>Redução BC %</label><input class="form-control" type="number" step="0.01" name="reducao_base_calculo"></div>
<div class="form-group col-md-2"><label>MVA Origem %</label><input class="form-control" type="number" step="0.01" name="mva_origem"></div>
<div class="form-group col-md-2"><label>Aliq. ICMS %</label><input class="form-control" type="number" step="0.01" name="aliquota_icms"></div>
<div class="form-group col-md-2"><label>Aliq. PIS %</label><input class="form-control" type="number" step="0.01" name="aliquota_pis"></div>
<div class="form-group col-md-2"><label>Aliq. COFINS %</label><input class="form-control" type="number" step="0.01" name="aliquota_cofins"></div>
<div class="form-group col-md-2"><label>Preço venda</label><input class="form-control" type="number" step="0.01" name="preco_venda" required></div>
</div>
<div class="form-row">
<div class="form-group col-md-2"><label>Validade (dias)</label><input class="form-control" type="number" name="validade_dias"></div>
<div class="form-group col-md-2"><label>Peso (kg)</label><input class="form-control" type="number" step="0.001" name="peso"></div>
</div>
<button class="btn btn-primary">Salvar produto</button>
</form>
</div></div>
<div class="card"><div class="card-body"><table class="table table-striped datatable"><thead><tr><th>Código</th><th>Descrição</th><th>UN</th><th>NCM</th><th>Preço</th><th>Status</th></tr></thead><tbody>
<?php foreach($produtos as $p): ?><tr><td><?= sanitizar($p['codigo']); ?></td><td><?= sanitizar($p['descricao']); ?></td><td><?= sanitizar($p['unidade_medida']); ?></td><td><?= sanitizar($p['ncm']); ?></td><td><?= moedaBr((float)$p['preco_venda']); ?></td><td><?= (int)$p['ativo']===1?'Ativo':'Inativo'; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
