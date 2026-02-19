import base64
import json
import socket
import threading
import time
from pathlib import Path
from urllib.error import HTTPError
from urllib.request import Request, urlopen

from app.main import DB_PATH, run_server


def free_port() -> int:
    s = socket.socket()
    s.bind(("127.0.0.1", 0))
    p = s.getsockname()[1]
    s.close()
    return p


def req(method: str, url: str, payload=None, headers=None):
    data = None
    h = {"Content-Type": "application/json"}
    if headers:
        h.update(headers)
    if payload is not None:
        data = json.dumps(payload).encode()
    r = Request(url, data=data, headers=h, method=method)
    try:
        with urlopen(r, timeout=5) as resp:
            return resp.status, json.loads(resp.read().decode())
    except HTTPError as e:
        return e.code, json.loads(e.read().decode())


def start_api(tmp_path):
    if DB_PATH.exists():
        DB_PATH.unlink()
    port = free_port()
    t = threading.Thread(target=run_server, kwargs={"host": "127.0.0.1", "port": port}, daemon=True)
    t.start()
    base = f"http://127.0.0.1:{port}"
    for _ in range(30):
        try:
            status, _ = req("GET", f"{base}/health")
            if status == 200:
                return base
        except Exception:
            pass
        time.sleep(0.1)
    raise RuntimeError("server not ready")


def auth(base):
    status, body = req("POST", f"{base}/auth/login", {"username": "maria", "password": "admin"})
    assert status == 200
    return {"X-User": "maria", "X-CSRF-Token": body["csrf_token"]}


def test_full_flow_crud_orders_receivables_reports_upload_audit(tmp_path):
    base = start_api(tmp_path)

    status, _ = req("POST", f"{base}/customer-groups", {"name": "x", "code": "x"})
    assert status == 403

    headers = auth(base)

    status, cg = req("POST", f"{base}/customer-groups", {"name": "Atacado", "code": "ATA"}, headers)
    assert status == 201
    status, pm = req("POST", f"{base}/payment-methods", {"name": "PIX", "code": "PIX"}, headers)
    assert status == 201
    status, prod = req("POST", f"{base}/products", {"code": "P1", "name": "Produto", "base_price": 100, "stock": 20}, headers)
    assert status == 201

    status, pt = req(
        "POST",
        f"{base}/price-tables",
        {"name": "Tabela A", "customer_group_id": cg["id"], "product_id": prod["id"], "price": 80},
        headers,
    )
    assert status == 201

    status, listing = req("GET", f"{base}/customer-groups?q=ata&page=1&per_page=10")
    assert status == 200 and listing["total"] >= 1

    status, order = req(
        "POST",
        f"{base}/orders",
        {
            "customer_name": "Cliente A",
            "customer_group_id": cg["id"],
            "discount": 10,
            "items": [{"product_id": prod["id"], "quantity": 2}],
        },
        headers,
    )
    assert status == 201
    assert order["subtotal"] == 160 and order["total"] == 150

    status, _ = req("PUT", f"{base}/orders/{order['id']}", {"status": "faturado"}, headers)
    assert status == 200

    status, title = req("POST", f"{base}/orders/{order['id']}/generate-title", {"payment_method_id": pm["id"]}, headers)
    assert status == 201 and title["balance"] == 150

    status, partial = req("POST", f"{base}/receivables/{title['id']}/settle", {"amount": 100, "payment_method_id": pm["id"]}, headers)
    assert status == 200 and partial["status"] == "partial"

    status, reopened = req("POST", f"{base}/receivables/{title['id']}/reopen", {"amount": 30, "reason": "chargeback"}, headers)
    assert status == 200 and reopened["status"] == "open"

    status, report = req("GET", f"{base}/reports/receivables?status=open")
    assert status == 200 and "A4" in report["html"]

    img = base64.b64encode(b"\x89PNG\r\n\x1a\n" + b"0" * 256).decode()
    status, pimg = req(
        "POST",
        f"{base}/upload/product-image",
        {"product_id": prod["id"], "content_type": "image/png", "content_b64": img},
        headers,
    )
    assert status == 200 and pimg["url"].endswith(".png")

    status, logo = req("POST", f"{base}/upload/company-logo", {"content_type": "image/png", "content_b64": img}, headers)
    assert status == 200 and logo["url"].endswith(".png")

    status, audits = req("GET", f"{base}/audits?user=maria&entity=orders")
    assert status == 200 and len(audits["items"]) >= 1


def test_rate_limit_login(tmp_path):
    base = start_api(tmp_path)
    for _ in range(5):
        status, _ = req("POST", f"{base}/auth/login", {"username": "u", "password": "bad"})
        assert status == 401
    status, body = req("POST", f"{base}/auth/login", {"username": "u", "password": "bad"})
    assert status == 429
    assert "Bloqueio" in body["error"]
