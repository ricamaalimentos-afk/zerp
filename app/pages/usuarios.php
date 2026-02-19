<?php
$pdo = obterConexao();
$usuarios = $pdo->query('SELECT codigo, nome, email, nivel, comissao, limite_desconto, ativo FROM usuarios ORDER BY id DESC')->fetchAll();
?>
<h1 class="h3 mb-3 text-gray-800">Cadastro de Usuários</h1>
<div class="card mb-4"><div class="card-body">
<form method="post" class="text-uppercase-auto">
    <input type="hidden" name="acao" value="salvar_usuario">
    <div class="form-row">
        <div class="form-group col-md-2"><label>Código</label><input class="form-control" name="codigo" value="U<?= time(); ?>" required></div>
        <div class="form-group col-md-4"><label>Nome</label><input class="form-control upper" name="nome" required></div>
        <div class="form-group col-md-3"><label>Apelido</label><input class="form-control upper" name="apelido"></div>
        <div class="form-group col-md-3"><label>E-mail</label><input type="email" class="form-control" name="email" required></div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-2"><label>CEP</label><input class="form-control cep" name="cep"></div>
        <div class="form-group col-md-4"><label>Endereço</label><input class="form-control upper endereco" name="endereco"></div>
        <div class="form-group col-md-3"><label>Bairro</label><input class="form-control upper bairro" name="bairro"></div>
        <div class="form-group col-md-2"><label>Cidade</label><input class="form-control upper cidade" name="cidade"></div>
        <div class="form-group col-md-1"><label>UF</label><input class="form-control upper uf" name="uf"></div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-2"><label>Fone</label><input class="form-control fone" name="fone"></div>
        <div class="form-group col-md-2"><label>WhatsApp</label><input class="form-control fone" name="whatsapp"></div>
        <div class="form-group col-md-2"><label>Comissão %</label><input class="form-control" type="number" step="0.01" name="comissao"></div>
        <div class="form-group col-md-2"><label>Desc. Máx %</label><input class="form-control" type="number" step="0.01" name="limite_desconto"></div>
        <div class="form-group col-md-2"><label>Nível</label><select class="form-control" name="nivel"><option>VENDEDOR</option><option>ADMIN</option></select></div>
        <div class="form-group col-md-2"><label>Senha</label><input class="form-control" name="senha" type="password" value="123456"></div>
    </div>
    <div class="form-row">
        <?php foreach (['usuarios','clientes','produtos','vendas','receber','configuracoes'] as $menu): ?>
            <div class="form-group col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" checked name="permissoes[]" value="<?= $menu; ?>"><label class="form-check-label"><?= ucfirst($menu); ?></label></div></div>
        <?php endforeach; ?>
        <div class="form-group col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="ativo" checked><label class="form-check-label">Ativo</label></div></div>
    </div>
    <button class="btn btn-primary">Salvar usuário</button>
</form>
</div></div>

<div class="card"><div class="card-body"><table class="table table-striped datatable"><thead><tr><th>Código</th><th>Nome</th><th>E-mail</th><th>Nível</th><th>Comissão</th><th>Desc. Máx</th><th>Status</th></tr></thead><tbody>
<?php foreach ($usuarios as $u): ?><tr><td><?= sanitizar($u['codigo']); ?></td><td><?= sanitizar($u['nome']); ?></td><td><?= sanitizar($u['email']); ?></td><td><?= sanitizar($u['nivel']); ?></td><td><?= number_format((float)$u['comissao'],2,',','.'); ?>%</td><td><?= number_format((float)$u['limite_desconto'],2,',','.'); ?>%</td><td><?= (int)$u['ativo']===1?'Ativo':'Inativo'; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
