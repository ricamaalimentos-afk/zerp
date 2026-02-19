<?php
$pdo = obterConexao();
$clientes = $pdo->query('SELECT id, nome_razao, cpf_cnpj, telefone, limite_credito, ativo FROM clientes ORDER BY id DESC')->fetchAll();
$grupos = $pdo->query('SELECT id, nome FROM grupos_clientes WHERE ativo = 1 ORDER BY nome')->fetchAll();
$tabelas = $pdo->query('SELECT id, nome FROM tabelas_preco WHERE ativo = 1 ORDER BY nome')->fetchAll();
?>
<h1 class="h3 mb-3 text-gray-800">Cadastro de Clientes</h1>
<div class="card mb-4"><div class="card-body">
<form method="post">
<input type="hidden" name="acao" value="salvar_cliente">
<div class="form-row">
<div class="form-group col-md-4"><label>Nome/Razão</label><input class="form-control upper" name="nome_razao" required></div>
<div class="form-group col-md-3"><label>Apelido/Empresa</label><input class="form-control upper" name="apelido_empresa"></div>
<div class="form-group col-md-2"><label>CPF/CNPJ</label><input class="form-control" name="cpf_cnpj"></div>
<div class="form-group col-md-3"><label>Contato</label><input class="form-control upper" name="contato"></div>
</div>
<div class="form-row">
<div class="form-group col-md-2"><label>CEP</label><input class="form-control cep" name="cep"></div>
<div class="form-group col-md-4"><label>Endereço</label><input class="form-control upper endereco" name="endereco"></div>
<div class="form-group col-md-2"><label>Bairro</label><input class="form-control upper bairro" name="bairro"></div>
<div class="form-group col-md-2"><label>Cidade</label><input class="form-control upper cidade" name="cidade"></div>
<div class="form-group col-md-1"><label>UF</label><input class="form-control upper uf" name="uf"></div>
<div class="form-group col-md-1"><label>IBGE</label><input class="form-control" name="cod_municipio_ibge"></div>
</div>
<div class="form-row">
<div class="form-group col-md-2"><label>Nascimento</label><input class="form-control data" name="data_nascimento" placeholder="dd/mm/aaaa"></div>
<div class="form-group col-md-2"><label>Telefone</label><input class="form-control fone" name="telefone"></div>
<div class="form-group col-md-2"><label>WhatsApp</label><input class="form-control fone" name="whatsapp"></div>
<div class="form-group col-md-3"><label>E-mail</label><input class="form-control" type="email" name="email"></div>
<div class="form-group col-md-1"><label>Cód. País</label><input class="form-control" name="codigo_pais" value="1058"></div>
<div class="form-group col-md-2"><label>País</label><input class="form-control upper" name="pais" value="BRASIL"></div>
</div>
<div class="form-row">
<div class="form-group col-md-2"><label>Limite Crédito</label><input class="form-control" type="number" step="0.01" name="limite_credito"></div>
<div class="form-group col-md-3"><label>Tabela de Preço</label><select class="form-control" name="tabela_preco_id"><option value="">Padrão</option><?php foreach($tabelas as $t): ?><option value="<?= $t['id']; ?>"><?= sanitizar($t['nome']); ?></option><?php endforeach; ?></select></div>
<div class="form-group col-md-3"><label>Grupo Cliente</label><select class="form-control" name="grupo_cliente_id"><option value="">Sem grupo</option><?php foreach($grupos as $g): ?><option value="<?= $g['id']; ?>"><?= sanitizar($g['nome']); ?></option><?php endforeach; ?></select></div>
<div class="form-group col-md-4"><label>Observações</label><input class="form-control" name="observacoes"></div>
</div>
<button class="btn btn-primary">Salvar cliente</button>
</form>
</div></div>
<div class="card"><div class="card-body"><table class="table table-striped datatable"><thead><tr><th>ID</th><th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th><th>Limite</th><th>Status</th></tr></thead><tbody>
<?php foreach($clientes as $c): ?><tr><td><?= $c['id']; ?></td><td><?= sanitizar($c['nome_razao']); ?></td><td><?= sanitizar($c['cpf_cnpj']); ?></td><td><?= sanitizar($c['telefone']); ?></td><td><?= moedaBr((float)$c['limite_credito']); ?></td><td><?= (int)$c['ativo']===1?'Ativo':'Inativo'; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
