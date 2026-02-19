<h1 class="h3 mb-3">Configurações Gerais e Impressão</h1>
<form method="post" class="card card-body mb-3">
<div class="form-row"><input name="nome_sistema" class="form-control col" placeholder="Nome sistema"><input name="versao" class="form-control col" placeholder="Versão"><input name="tema_cor" class="form-control col" placeholder="Cor tema"></div>
<div class="form-row mt-2"><input name="fonte" class="form-control col" placeholder="Fonte"><input name="logo" class="form-control col" placeholder="Logo URL"><input name="cabecalho_relatorio" class="form-control col" placeholder="Cabeçalho relatório"></div>
<div class="form-row mt-2"><input name="rodape_relatorio" class="form-control col" placeholder="Rodapé relatório"><input name="informacoes_relatorio" class="form-control col" placeholder="Informações a exibir"></div>
<button class="btn btn-primary mt-2">Salvar</button></form>
<?php if(!empty($registros)): $cfg=$registros[0]; ?>
<div class="alert alert-info">Configuração atual: <?= htmlspecialchars($cfg['nome_sistema'] ?? '') ?> - versão <?= htmlspecialchars($cfg['versao'] ?? '') ?></div>
<?php endif; ?>
