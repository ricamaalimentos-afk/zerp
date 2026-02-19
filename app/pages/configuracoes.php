<?php
$pdo = obterConexao();
$config = $pdo->query('SELECT * FROM configuracoes_gerais ORDER BY id DESC LIMIT 1')->fetch();
?>
<h1 class="h3 mb-3 text-gray-800">Configurações Gerais</h1>
<div class="card"><div class="card-body">
<p><strong>Sistema:</strong> <?= sanitizar((string)($config['nome_sistema'] ?? APP_NOME)); ?></p>
<p><strong>Versão:</strong> <?= sanitizar((string)($config['versao'] ?? APP_VERSAO)); ?></p>
<p><strong>Cores do tema:</strong> Primária <?= sanitizar((string)($config['cor_primaria'] ?? '#4e73df')); ?> / Secundária <?= sanitizar((string)($config['cor_secundaria'] ?? '#1cc88a')); ?></p>
<p><strong>Impressão:</strong> Cabeçalho e rodapé parametrizados em banco para evolução do módulo de relatórios.</p>
</div></div>
