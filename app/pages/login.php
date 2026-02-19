<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= APP_NOME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-4">
                    <h1 class="h4 text-gray-900 mb-4 text-center">Acesso ao <?= APP_NOME; ?></h1>
                    <?php if (!empty($_SESSION['erro'])): ?>
                        <div class="alert alert-danger"><?= sanitizar($_SESSION['erro']); unset($_SESSION['erro']); ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="acao" value="login">
                        <div class="form-group"><input type="email" name="email" class="form-control" placeholder="E-mail" required></div>
                        <div class="form-group"><input type="password" name="senha" class="form-control" placeholder="Senha" required></div>
                        <button class="btn btn-primary btn-block" type="submit">Entrar</button>
                    </form>
                    <hr>
                    <small>Usuário inicial: admin@zerp.local / senha: admin123</small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
