<h1 class="h3 mb-3">Pedidos / Vendas</h1>
<form method="post" class="card card-body mb-3">
<div class="form-row"><select name="cliente_id" class="form-control col" required><option value="">Cliente</option><?php foreach($clientes as $c):?><option value="<?=$c['id']?>"><?=$c['nome_razao_social']?></option><?php endforeach;?></select>
<select name="vendedor_id" class="form-control col"><option value="">Vendedor (auto)</option><?php foreach($usuarios as $u):?><option value="<?=$u['id']?>"><?=$u['nome']?></option><?php endforeach;?></select>
<input name="valor_produtos" class="form-control col" placeholder="Valor produtos" required></div>
<div class="form-row mt-2"><input name="frete" class="form-control col" placeholder="Frete" value="0"><input name="icms_st" class="form-control col" placeholder="ICMS ST" value="0"><input name="desconto" class="form-control col" placeholder="Desconto" value="0"></div>
<textarea class="form-control mt-2" name="observacoes" placeholder="Observações"></textarea>
<button class="btn btn-primary mt-2">Salvar pedido aberto</button></form>
<table class="table tabela-dados"><thead><tr><th>ID</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($vendas as $v):?><tr><td><?=$v['id']?></td><td><?=$v['cliente_nome']?></td><td><?=$v['vendedor_nome']?></td><td>R$ <?=number_format((float)$v['valor_total'],2,',','.')?></td><td><?=$v['status']?></td><td><?php if($v['status']==='aberto'):?><a class="btn btn-sm btn-success" href="<?=$config['app']['url_base']?>/vendas/finalizar/<?=$v['id']?>">Finalizar</a><?php endif;?></td></tr><?php endforeach;?></tbody></table>
