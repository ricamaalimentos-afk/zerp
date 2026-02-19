<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-cubes"></i></div>
        <div class="sidebar-brand-text mx-3">ZERP</div>
    </a>

    <hr class="sidebar-divider">

    <li class="nav-item"><a class="nav-link" href="index.php?rota=dashboard"><i class="fas fa-fw fa-home"></i><span>Dashboard</span></a></li>

    <?php if (menuPermitido('usuarios')): ?><li class="nav-item"><a class="nav-link" href="index.php?rota=usuarios"><i class="fas fa-users"></i><span>Usuários</span></a></li><?php endif; ?>
    <?php if (menuPermitido('clientes')): ?><li class="nav-item"><a class="nav-link" href="index.php?rota=clientes"><i class="fas fa-address-book"></i><span>Clientes</span></a></li><?php endif; ?>
    <?php if (menuPermitido('produtos')): ?><li class="nav-item"><a class="nav-link" href="index.php?rota=produtos"><i class="fas fa-box-open"></i><span>Produtos</span></a></li><?php endif; ?>
    <?php if (menuPermitido('vendas')): ?><li class="nav-item"><a class="nav-link" href="index.php?rota=vendas"><i class="fas fa-cash-register"></i><span>Pedidos/Vendas</span></a></li><?php endif; ?>
    <?php if (menuPermitido('receber')): ?><li class="nav-item"><a class="nav-link" href="index.php?rota=receber"><i class="fas fa-file-invoice-dollar"></i><span>Contas a Receber</span></a></li><?php endif; ?>
    <?php if (menuPermitido('configuracoes')): ?><li class="nav-item"><a class="nav-link" href="index.php?rota=configuracoes"><i class="fas fa-cogs"></i><span>Configurações</span></a></li><?php endif; ?>

    <hr class="sidebar-divider d-none d-md-block">
    <li class="nav-item"><a class="nav-link" href="index.php?rota=logout"><i class="fas fa-sign-out-alt"></i><span>Sair</span></a></li>
</ul>
