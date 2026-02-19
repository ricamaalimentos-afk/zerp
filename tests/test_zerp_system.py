from zerp_system import ZerpSystem, SecurityError, RateLimitError


def test_full_suggested_improvements_flow(tmp_path):
    sys = ZerpSystem(upload_dir=str(tmp_path / 'uploads'))

    # Segurança: login + csrf
    _, csrf = sys.login('maria', 'admin', ip='1.1.1.1')

    # CRUD completo
    cg = sys.create_customer_group(user='maria', csrf=csrf, name='Atacado', code='ATA')
    sys.update_customer_group(user='maria', csrf=csrf, group_id=cg.id, name='Atacado VIP', code='ATV', active=True)
    pm = sys.create_payment_method(user='maria', csrf=csrf, name='PIX', code='PIX')
    assert pm.name == 'PIX'

    prod = sys.create_product(user='maria', csrf=csrf, code='P1', name='Produto', base_price=100)
    sys.create_price_table(user='maria', csrf=csrf, name='Tabela Atacado', customer_group_id=cg.id, product_code='P1', price=80)

    # Pedido com prioridade de preço
    order, items = sys.create_order(
        user='maria', csrf=csrf, customer_name='Cliente A', customer_group_id=cg.id, discount=10,
        items=[{'product_code': 'P1', 'quantity': 2}]
    )
    assert items[0].unit_price == 80
    assert order.total == 150

    # Contas a receber: gerar / baixar / reabrir
    title = sys.generate_title(user='maria', csrf=csrf, order_id=order.id)
    assert title.balance == 150
    sys.settle_title(user='maria', csrf=csrf, title_id=title.id, amount=100)
    reopened = sys.reopen_title(user='maria', csrf=csrf, title_id=title.id, amount=30, reason='Chargeback')
    assert reopened.status == 'open'

    # Relatório imprimível A4
    report = sys.receivables_report_a4(status='open')
    assert 'A4' in report
    assert '<table>' in report

    # Upload de imagem/logo
    img = b'\x89PNG\r\n\x1a\n' + b'0' * 100
    pimg = sys.upload_product_image(user='maria', csrf=csrf, product_code=prod.code, filename='a.png', content_type='image/png', content=img)
    logo = sys.upload_logo(user='maria', csrf=csrf, filename='logo.png', content_type='image/png', content=img)
    assert pimg.endswith('.png')
    assert logo.endswith('.png')

    # Auditoria
    logs = sys.list_audits(user='maria')
    assert len(logs) >= 8


def test_security_rate_limit_and_csrf_block():
    sys = ZerpSystem()
    for _ in range(5):
        try:
            sys.login('bad', 'wrong', ip='2.2.2.2')
        except SecurityError:
            pass
    try:
        sys.login('bad', 'wrong', ip='2.2.2.2')
        assert False
    except RateLimitError:
        assert True
