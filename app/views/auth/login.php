<?php $config = require __DIR__ . '/../../config/config.php'; ?>
<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css">
<title>Login - ERP</title></head>
<body class="bg-gradient-primary"><div class="container"><div class="row justify-content-center"><div class="col-xl-4 col-lg-5 col-md-6"><div class="card o-hidden border-0 shadow-lg my-5"><div class="card-body p-4">
<h1 class="h4 text-gray-900 mb-4 text-center">ERP Zerp</h1>
<?php if (!empty($_SESSION['erro_login'])): ?><div class="alert alert-danger"><?=$_SESSION['erro_login']; unset($_SESSION['erro_login']);?></div><?php endif; ?>
<form method="post" action="<?= $config['app']['url_base'] ?>/login">
<input class="form-control mb-3" name="email" type="email" placeholder="E-mail" required>
<input class="form-control mb-3" name="senha" type="password" placeholder="Senha" required>
<button class="btn btn-primary btn-block" type="submit">Entrar</button>
</form></div></div></div></div></div></body></html>
