from __future__ import annotations

import base64
import hashlib
import json
import re
import sqlite3
import threading
import time
from dataclasses import dataclass
from datetime import datetime, timedelta
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlparse
from uuid import uuid4

DB_PATH = Path("zerp.sqlite3")
UPLOAD_DIR = Path("uploads")
UPLOAD_DIR.mkdir(exist_ok=True)


@dataclass
class AppState:
    csrf_tokens: dict[str, str]
    login_attempts: dict[str, list[float]]


STATE = AppState(csrf_tokens={}, login_attempts={})
LOCK = threading.Lock()


def get_db() -> sqlite3.Connection:
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


def apply_migrations() -> None:
    with get_db() as conn:
        conn.execute("CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY, name TEXT UNIQUE NOT NULL)")

        migrations = [
            (
                "001_core",
                """
                CREATE TABLE IF NOT EXISTS customer_groups (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    code TEXT NOT NULL UNIQUE,
                    active INTEGER NOT NULL DEFAULT 1,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_customer_groups_name ON customer_groups(name);
                CREATE INDEX IF NOT EXISTS idx_customer_groups_code ON customer_groups(code);

                CREATE TABLE IF NOT EXISTS payment_methods (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    code TEXT NOT NULL UNIQUE,
                    active INTEGER NOT NULL DEFAULT 1,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_payment_methods_name ON payment_methods(name);
                CREATE INDEX IF NOT EXISTS idx_payment_methods_code ON payment_methods(code);

                CREATE TABLE IF NOT EXISTS products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code TEXT NOT NULL UNIQUE,
                    name TEXT NOT NULL,
                    base_price REAL NOT NULL,
                    stock INTEGER NOT NULL DEFAULT 0,
                    image_url TEXT,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_products_code ON products(code);

                CREATE TABLE IF NOT EXISTS price_tables (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    customer_group_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    price REAL NOT NULL,
                    active INTEGER NOT NULL DEFAULT 1,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY(customer_group_id) REFERENCES customer_groups(id),
                    FOREIGN KEY(product_id) REFERENCES products(id)
                );
                CREATE INDEX IF NOT EXISTS idx_price_tables_group_product ON price_tables(customer_group_id, product_id);

                CREATE TABLE IF NOT EXISTS orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    customer_name TEXT NOT NULL,
                    customer_group_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'draft',
                    subtotal REAL NOT NULL DEFAULT 0,
                    discount REAL NOT NULL DEFAULT 0,
                    total REAL NOT NULL DEFAULT 0,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY(customer_group_id) REFERENCES customer_groups(id)
                );
                CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

                CREATE TABLE IF NOT EXISTS order_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    quantity INTEGER NOT NULL,
                    negotiated_price REAL,
                    unit_price REAL NOT NULL,
                    line_total REAL NOT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY(product_id) REFERENCES products(id)
                );

                CREATE TABLE IF NOT EXISTS receivable_titles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER NOT NULL,
                    payment_method_id INTEGER,
                    due_date TEXT NOT NULL,
                    amount REAL NOT NULL,
                    balance REAL NOT NULL,
                    status TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY(order_id) REFERENCES orders(id),
                    FOREIGN KEY(payment_method_id) REFERENCES payment_methods(id)
                );
                CREATE INDEX IF NOT EXISTS idx_receivable_titles_status_due ON receivable_titles(status, due_date);

                CREATE TABLE IF NOT EXISTS receivable_events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title_id INTEGER NOT NULL,
                    action TEXT NOT NULL,
                    amount REAL,
                    payment_method_id INTEGER,
                    reason TEXT,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY(title_id) REFERENCES receivable_titles(id),
                    FOREIGN KEY(payment_method_id) REFERENCES payment_methods(id)
                );

                CREATE TABLE IF NOT EXISTS audits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_name TEXT NOT NULL,
                    action TEXT NOT NULL,
                    entity TEXT NOT NULL,
                    entity_id TEXT NOT NULL,
                    before_json TEXT,
                    after_json TEXT,
                    ip TEXT,
                    created_at TEXT NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_audits_user_entity_date ON audits(user_name, entity, created_at);

                CREATE TABLE IF NOT EXISTS company_config (
                    id INTEGER PRIMARY KEY CHECK(id=1),
                    logo_url TEXT,
                    updated_at TEXT NOT NULL
                );
                INSERT OR IGNORE INTO company_config(id, logo_url, updated_at) VALUES(1, NULL, datetime('now'));
                """,
            )
        ]

        for name, script in migrations:
            exists = conn.execute("SELECT 1 FROM migrations WHERE name=?", (name,)).fetchone()
            if not exists:
                conn.executescript(script)
                conn.execute("INSERT INTO migrations(name) VALUES (?)", (name,))


def now_iso() -> str:
    return datetime.utcnow().isoformat()


def parse_json(handler: BaseHTTPRequestHandler) -> dict:
    length = int(handler.headers.get("Content-Length", "0"))
    body = handler.rfile.read(length) if length else b"{}"
    return json.loads(body.decode("utf-8"))


def send_json(handler: BaseHTTPRequestHandler, status: int, payload: dict | list):
    data = json.dumps(payload, ensure_ascii=False).encode("utf-8")
    handler.send_response(status)
    handler.send_header("Content-Type", "application/json; charset=utf-8")
    handler.send_header("Content-Length", str(len(data)))
    handler.end_headers()
    handler.wfile.write(data)


def paginate(query: dict) -> tuple[int, int]:
    page = int(query.get("page", ["1"])[0])
    per_page = int(query.get("per_page", ["20"])[0])
    page = max(page, 1)
    per_page = min(max(per_page, 1), 100)
    return page, per_page


def require_csrf(handler: BaseHTTPRequestHandler) -> str:
    user = handler.headers.get("X-User", "")
    token = handler.headers.get("X-CSRF-Token", "")
    if not user or STATE.csrf_tokens.get(user) != token:
        raise PermissionError("CSRF token inválido")
    return user


def audit(conn: sqlite3.Connection, user: str, action: str, entity: str, entity_id: str, ip: str, before: dict | None = None, after: dict | None = None):
    conn.execute(
        """INSERT INTO audits(user_name,action,entity,entity_id,before_json,after_json,ip,created_at)
           VALUES(?,?,?,?,?,?,?,?)""",
        (
            user,
            action,
            entity,
            entity_id,
            json.dumps(before, ensure_ascii=False) if before else None,
            json.dumps(after, ensure_ascii=False) if after else None,
            ip,
            now_iso(),
        ),
    )


def row_to_dict(row: sqlite3.Row) -> dict:
    return {k: row[k] for k in row.keys()}


def calculate_order_totals(conn: sqlite3.Connection, order_id: int):
    subtotal = conn.execute("SELECT COALESCE(SUM(line_total),0) AS s FROM order_items WHERE order_id=?", (order_id,)).fetchone()["s"]
    discount = conn.execute("SELECT discount FROM orders WHERE id=?", (order_id,)).fetchone()["discount"]
    total = max(float(subtotal) - float(discount), 0)
    conn.execute("UPDATE orders SET subtotal=?, total=?, updated_at=? WHERE id=?", (subtotal, total, now_iso(), order_id))


def resolve_price(conn: sqlite3.Connection, customer_group_id: int, product_id: int, negotiated_price: float | None) -> float:
    if negotiated_price is not None:
        return float(negotiated_price)
    row = conn.execute(
        "SELECT price FROM price_tables WHERE active=1 AND customer_group_id=? AND product_id=? ORDER BY id DESC LIMIT 1",
        (customer_group_id, product_id),
    ).fetchone()
    if row:
        return float(row["price"])
    p = conn.execute("SELECT base_price FROM products WHERE id=?", (product_id,)).fetchone()
    if not p:
        raise ValueError("Produto não encontrado")
    return float(p["base_price"])


class Handler(BaseHTTPRequestHandler):
    server_version = "ZerpHTTP/1.0"

    def do_GET(self):
        try:
            parsed = urlparse(self.path)
            path = parsed.path
            query = parse_qs(parsed.query)
            with get_db() as conn:
                if path == "/health":
                    return send_json(self, 200, {"ok": True})
                if path == "/customer-groups":
                    return self.list_entities(conn, "customer_groups", query)
                if path == "/payment-methods":
                    return self.list_entities(conn, "payment_methods", query)
                if path == "/price-tables":
                    return self.list_price_tables(conn, query)
                if path == "/products":
                    return self.list_entities(conn, "products", query, fields="id,code,name,base_price,stock,image_url")
                if path == "/reports/receivables":
                    return self.report_receivables(conn, query)
                if path == "/reports/orders":
                    return self.report_orders(conn, query)
                if path == "/audits":
                    return self.list_audits(conn, query)
            send_json(self, 404, {"error": "Rota não encontrada"})
        except Exception as e:  # noqa
            send_json(self, 400, {"error": str(e)})

    def do_POST(self):
        try:
            parsed = urlparse(self.path)
            path = parsed.path
            if path == "/auth/login":
                return self.login()
            user = require_csrf(self)
            with get_db() as conn:
                if path == "/customer-groups":
                    return self.create_entity(conn, user, "customer_groups", ["name", "code"], soft=True)
                if path == "/payment-methods":
                    return self.create_entity(conn, user, "payment_methods", ["name", "code"], soft=True)
                if path == "/products":
                    return self.create_product(conn, user)
                if path == "/price-tables":
                    return self.create_price_table(conn, user)
                if path == "/orders":
                    return self.create_order(conn, user)
                m = re.match(r"^/orders/(\d+)/items$", path)
                if m:
                    return self.add_order_item(conn, user, int(m.group(1)))
                m = re.match(r"^/orders/(\d+)/generate-title$", path)
                if m:
                    return self.generate_title(conn, user, int(m.group(1)))
                m = re.match(r"^/receivables/(\d+)/settle$", path)
                if m:
                    return self.settle_title(conn, user, int(m.group(1)))
                m = re.match(r"^/receivables/(\d+)/reopen$", path)
                if m:
                    return self.reopen_title(conn, user, int(m.group(1)))
                if path == "/upload/product-image":
                    return self.upload_product_image(conn, user)
                if path == "/upload/company-logo":
                    return self.upload_company_logo(conn, user)
            send_json(self, 404, {"error": "Rota não encontrada"})
        except PermissionError as e:
            send_json(self, 403, {"error": str(e)})
        except Exception as e:  # noqa
            send_json(self, 400, {"error": str(e)})

    def do_PUT(self):
        try:
            user = require_csrf(self)
            parsed = urlparse(self.path)
            path = parsed.path
            with get_db() as conn:
                m = re.match(r"^/customer-groups/(\d+)$", path)
                if m:
                    return self.update_entity(conn, user, "customer_groups", int(m.group(1)), ["name", "code", "active"])
                m = re.match(r"^/payment-methods/(\d+)$", path)
                if m:
                    return self.update_entity(conn, user, "payment_methods", int(m.group(1)), ["name", "code", "active"])
                m = re.match(r"^/price-tables/(\d+)$", path)
                if m:
                    return self.update_price_table(conn, user, int(m.group(1)))
                m = re.match(r"^/orders/(\d+)$", path)
                if m:
                    return self.update_order(conn, user, int(m.group(1)))
                m = re.match(r"^/orders/(\d+)/items/(\d+)$", path)
                if m:
                    return self.update_order_item(conn, user, int(m.group(1)), int(m.group(2)))
            send_json(self, 404, {"error": "Rota não encontrada"})
        except PermissionError as e:
            send_json(self, 403, {"error": str(e)})
        except Exception as e:  # noqa
            send_json(self, 400, {"error": str(e)})

    def do_DELETE(self):
        try:
            user = require_csrf(self)
            path = urlparse(self.path).path
            with get_db() as conn:
                m = re.match(r"^/customer-groups/(\d+)$", path)
                if m:
                    return self.soft_delete(conn, user, "customer_groups", int(m.group(1)), usage_table="orders", usage_field="customer_group_id")
                m = re.match(r"^/payment-methods/(\d+)$", path)
                if m:
                    return self.soft_delete(conn, user, "payment_methods", int(m.group(1)), usage_table="receivable_titles", usage_field="payment_method_id")
                m = re.match(r"^/price-tables/(\d+)$", path)
                if m:
                    return self.soft_delete(conn, user, "price_tables", int(m.group(1)), usage_table=None, usage_field=None)
                m = re.match(r"^/orders/(\d+)/items/(\d+)$", path)
                if m:
                    return self.delete_order_item(conn, user, int(m.group(1)), int(m.group(2)))
            send_json(self, 404, {"error": "Rota não encontrada"})
        except PermissionError as e:
            send_json(self, 403, {"error": str(e)})
        except Exception as e:  # noqa
            send_json(self, 400, {"error": str(e)})

    def list_entities(self, conn, table, query, fields="id,name,code,active"):
        page, per_page = paginate(query)
        q = query.get("q", [""])[0].strip().lower()
        where = "WHERE 1=1"
        params = []
        if q:
            where += " AND (LOWER(name) LIKE ? OR LOWER(code) LIKE ?)"
            params += [f"%{q}%", f"%{q}%"]
        total = conn.execute(f"SELECT COUNT(*) c FROM {table} {where}", params).fetchone()["c"]
        offset = (page - 1) * per_page
        rows = conn.execute(f"SELECT {fields} FROM {table} {where} ORDER BY id LIMIT ? OFFSET ?", (*params, per_page, offset)).fetchall()
        send_json(self, 200, {"items": [row_to_dict(r) for r in rows], "page": page, "per_page": per_page, "total": total})

    def create_entity(self, conn, user, table, required_fields, soft=False):
        data = parse_json(self)
        for f in required_fields:
            if not data.get(f):
                raise ValueError(f"{f} é obrigatório")
        now = now_iso()
        cols = ",".join(required_fields + (["active"] if soft else []) + ["created_at", "updated_at"])
        vals = [data[f] for f in required_fields] + ([int(bool(data.get("active", True)))] if soft else []) + [now, now]
        placeholders = ",".join(["?"] * len(vals))
        cur = conn.execute(f"INSERT INTO {table}({cols}) VALUES({placeholders})", vals)
        obj = conn.execute(f"SELECT * FROM {table} WHERE id=?", (cur.lastrowid,)).fetchone()
        audit(conn, user, "create", table, str(cur.lastrowid), self.client_address[0], after=row_to_dict(obj))
        conn.commit()
        send_json(self, 201, row_to_dict(obj))

    def update_entity(self, conn, user, table, obj_id, allowed_fields):
        current = conn.execute(f"SELECT * FROM {table} WHERE id=?", (obj_id,)).fetchone()
        if not current:
            raise ValueError("Registro não encontrado")
        data = parse_json(self)
        sets = []
        vals = []
        for f in allowed_fields:
            if f in data:
                sets.append(f"{f}=?")
                vals.append(data[f])
        sets.append("updated_at=?")
        vals.append(now_iso())
        vals.append(obj_id)
        conn.execute(f"UPDATE {table} SET {','.join(sets)} WHERE id=?", vals)
        updated = conn.execute(f"SELECT * FROM {table} WHERE id=?", (obj_id,)).fetchone()
        audit(conn, user, "update", table, str(obj_id), self.client_address[0], before=row_to_dict(current), after=row_to_dict(updated))
        conn.commit()
        send_json(self, 200, row_to_dict(updated))

    def soft_delete(self, conn, user, table, obj_id, usage_table, usage_field):
        current = conn.execute(f"SELECT * FROM {table} WHERE id=?", (obj_id,)).fetchone()
        if not current:
            raise ValueError("Registro não encontrado")
        if usage_table and usage_field:
            used = conn.execute(f"SELECT COUNT(*) c FROM {usage_table} WHERE {usage_field}=?", (obj_id,)).fetchone()["c"]
            if used > 0:
                raise ValueError("Registro em uso; exclusão não permitida")
        conn.execute(f"UPDATE {table} SET active=0, updated_at=? WHERE id=?", (now_iso(), obj_id))
        updated = conn.execute(f"SELECT * FROM {table} WHERE id=?", (obj_id,)).fetchone()
        audit(conn, user, "soft_delete", table, str(obj_id), self.client_address[0], before=row_to_dict(current), after=row_to_dict(updated))
        conn.commit()
        send_json(self, 200, {"ok": True})

    def create_product(self, conn, user):
        data = parse_json(self)
        for f in ["code", "name", "base_price"]:
            if data.get(f) in (None, ""):
                raise ValueError(f"{f} é obrigatório")
        now = now_iso()
        cur = conn.execute(
            "INSERT INTO products(code,name,base_price,stock,image_url,created_at,updated_at) VALUES(?,?,?,?,?,?,?)",
            (data["code"], data["name"], float(data["base_price"]), int(data.get("stock", 0)), None, now, now),
        )
        row = conn.execute("SELECT * FROM products WHERE id=?", (cur.lastrowid,)).fetchone()
        audit(conn, user, "create", "products", str(cur.lastrowid), self.client_address[0], after=row_to_dict(row))
        conn.commit()
        send_json(self, 201, row_to_dict(row))

    def create_price_table(self, conn, user):
        data = parse_json(self)
        for f in ["name", "customer_group_id", "product_id", "price"]:
            if data.get(f) in (None, ""):
                raise ValueError(f"{f} é obrigatório")
        now = now_iso()
        cur = conn.execute(
            "INSERT INTO price_tables(name,customer_group_id,product_id,price,active,created_at,updated_at) VALUES(?,?,?,?,1,?,?)",
            (data["name"], int(data["customer_group_id"]), int(data["product_id"]), float(data["price"]), now, now),
        )
        row = conn.execute("SELECT * FROM price_tables WHERE id=?", (cur.lastrowid,)).fetchone()
        audit(conn, user, "create", "price_tables", str(cur.lastrowid), self.client_address[0], after=row_to_dict(row))
        conn.commit()
        send_json(self, 201, row_to_dict(row))

    def list_price_tables(self, conn, query):
        page, per_page = paginate(query)
        q = query.get("q", [""])[0].strip().lower()
        where = "WHERE 1=1"
        params = []
        if q:
            where += " AND LOWER(pt.name) LIKE ?"
            params.append(f"%{q}%")
        total = conn.execute(f"SELECT COUNT(*) c FROM price_tables pt {where}", params).fetchone()["c"]
        offset = (page - 1) * per_page
        rows = conn.execute(
            f"""
            SELECT pt.*, cg.name AS customer_group_name, p.code AS product_code
            FROM price_tables pt
            JOIN customer_groups cg ON cg.id=pt.customer_group_id
            JOIN products p ON p.id=pt.product_id
            {where}
            ORDER BY pt.id LIMIT ? OFFSET ?
            """,
            (*params, per_page, offset),
        ).fetchall()
        send_json(self, 200, {"items": [row_to_dict(r) for r in rows], "page": page, "per_page": per_page, "total": total})

    def update_price_table(self, conn, user, obj_id):
        current = conn.execute("SELECT * FROM price_tables WHERE id=?", (obj_id,)).fetchone()
        if not current:
            raise ValueError("Tabela não encontrada")
        data = parse_json(self)
        fields = ["name", "customer_group_id", "product_id", "price", "active"]
        sets, vals = [], []
        for f in fields:
            if f in data:
                sets.append(f"{f}=?")
                vals.append(data[f])
        sets.append("updated_at=?")
        vals.append(now_iso())
        vals.append(obj_id)
        conn.execute(f"UPDATE price_tables SET {','.join(sets)} WHERE id=?", vals)
        updated = conn.execute("SELECT * FROM price_tables WHERE id=?", (obj_id,)).fetchone()
        audit(conn, user, "update", "price_tables", str(obj_id), self.client_address[0], before=row_to_dict(current), after=row_to_dict(updated))
        conn.commit()
        send_json(self, 200, row_to_dict(updated))

    def create_order(self, conn, user):
        data = parse_json(self)
        for f in ["customer_name", "customer_group_id", "items"]:
            if data.get(f) in (None, ""):
                raise ValueError(f"{f} é obrigatório")
        now = now_iso()
        cur = conn.execute(
            "INSERT INTO orders(customer_name,customer_group_id,status,subtotal,discount,total,created_at,updated_at) VALUES(?,?, 'draft', 0, ?, 0, ?, ?)",
            (data["customer_name"], int(data["customer_group_id"]), float(data.get("discount", 0)), now, now),
        )
        order_id = cur.lastrowid
        for item in data["items"]:
            self._upsert_item(conn, order_id, item, None)
        calculate_order_totals(conn, order_id)
        row = conn.execute("SELECT * FROM orders WHERE id=?", (order_id,)).fetchone()
        audit(conn, user, "create", "orders", str(order_id), self.client_address[0], after=row_to_dict(row))
        conn.commit()
        send_json(self, 201, row_to_dict(row))

    def _upsert_item(self, conn, order_id: int, item_data: dict, item_id: int | None):
        product_id = int(item_data["product_id"])
        quantity = int(item_data["quantity"])
        product = conn.execute("SELECT * FROM products WHERE id=?", (product_id,)).fetchone()
        if not product:
            raise ValueError("Produto não encontrado")
        if quantity <= 0:
            raise ValueError("Quantidade inválida")
        if product["stock"] < quantity:
            raise ValueError("Estoque insuficiente")
        customer_group_id = conn.execute("SELECT customer_group_id FROM orders WHERE id=?", (order_id,)).fetchone()["customer_group_id"]
        negotiated = item_data.get("negotiated_price")
        price = resolve_price(conn, customer_group_id, product_id, negotiated)
        total = price * quantity
        now = now_iso()
        if item_id is None:
            conn.execute(
                "INSERT INTO order_items(order_id,product_id,quantity,negotiated_price,unit_price,line_total,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)",
                (order_id, product_id, quantity, negotiated, price, total, now, now),
            )
        else:
            conn.execute(
                "UPDATE order_items SET product_id=?, quantity=?, negotiated_price=?, unit_price=?, line_total=?, updated_at=? WHERE id=? AND order_id=?",
                (product_id, quantity, negotiated, price, total, now, item_id, order_id),
            )

    def add_order_item(self, conn, user, order_id):
        data = parse_json(self)
        self._upsert_item(conn, order_id, data, None)
        calculate_order_totals(conn, order_id)
        row = conn.execute("SELECT * FROM orders WHERE id=?", (order_id,)).fetchone()
        audit(conn, user, "add_item", "orders", str(order_id), self.client_address[0], after=row_to_dict(row))
        conn.commit()
        send_json(self, 201, row_to_dict(row))

    def update_order_item(self, conn, user, order_id, item_id):
        before = conn.execute("SELECT * FROM order_items WHERE id=? AND order_id=?", (item_id, order_id)).fetchone()
        if not before:
            raise ValueError("Item não encontrado")
        data = parse_json(self)
        self._upsert_item(conn, order_id, data, item_id)
        calculate_order_totals(conn, order_id)
        after = conn.execute("SELECT * FROM order_items WHERE id=?", (item_id,)).fetchone()
        if before["negotiated_price"] != after["negotiated_price"]:
            audit(conn, user, "manual_price_change", "order_items", str(item_id), self.client_address[0], before=row_to_dict(before), after=row_to_dict(after))
        conn.commit()
        send_json(self, 200, row_to_dict(after))

    def delete_order_item(self, conn, user, order_id, item_id):
        before = conn.execute("SELECT * FROM order_items WHERE id=? AND order_id=?", (item_id, order_id)).fetchone()
        if not before:
            raise ValueError("Item não encontrado")
        conn.execute("DELETE FROM order_items WHERE id=? AND order_id=?", (item_id, order_id))
        calculate_order_totals(conn, order_id)
        audit(conn, user, "remove_item", "order_items", str(item_id), self.client_address[0], before=row_to_dict(before))
        conn.commit()
        send_json(self, 200, {"ok": True})

    def update_order(self, conn, user, order_id):
        order = conn.execute("SELECT * FROM orders WHERE id=?", (order_id,)).fetchone()
        if not order:
            raise ValueError("Pedido não encontrado")
        data = parse_json(self)
        fields = ["customer_name", "customer_group_id", "status", "discount"]
        sets, vals = [], []
        for f in fields:
            if f in data:
                sets.append(f"{f}=?")
                vals.append(data[f])
        sets.append("updated_at=?")
        vals.append(now_iso())
        vals.append(order_id)
        conn.execute(f"UPDATE orders SET {','.join(sets)} WHERE id=?", vals)
        calculate_order_totals(conn, order_id)
        updated = conn.execute("SELECT * FROM orders WHERE id=?", (order_id,)).fetchone()
        audit(conn, user, "update", "orders", str(order_id), self.client_address[0], before=row_to_dict(order), after=row_to_dict(updated))
        conn.commit()
        send_json(self, 200, row_to_dict(updated))

    def generate_title(self, conn, user, order_id):
        data = parse_json(self)
        due_days = int(data.get("due_days", 30))
        payment_method_id = data.get("payment_method_id")
        order = conn.execute("SELECT * FROM orders WHERE id=?", (order_id,)).fetchone()
        if not order:
            raise ValueError("Pedido não encontrado")
        if order["status"] != "faturado":
            raise ValueError("Pedido precisa estar faturado")
        due_date = (datetime.utcnow() + timedelta(days=due_days)).date().isoformat()
        now = now_iso()
        cur = conn.execute(
            "INSERT INTO receivable_titles(order_id,payment_method_id,due_date,amount,balance,status,created_at,updated_at) VALUES(?,?,?,?,?,'open',?,?)",
            (order_id, payment_method_id, due_date, order["total"], order["total"], now, now),
        )
        title_id = cur.lastrowid
        conn.execute(
            "INSERT INTO receivable_events(title_id,action,amount,payment_method_id,reason,created_at) VALUES(?,?,?,?,?,?)",
            (title_id, "generated", order["total"], payment_method_id, None, now),
        )
        row = conn.execute("SELECT * FROM receivable_titles WHERE id=?", (title_id,)).fetchone()
        audit(conn, user, "generate", "receivable_titles", str(title_id), self.client_address[0], after=row_to_dict(row))
        conn.commit()
        send_json(self, 201, row_to_dict(row))

    def settle_title(self, conn, user, title_id):
        data = parse_json(self)
        amount = float(data["amount"])
        payment_method_id = data.get("payment_method_id")
        t = conn.execute("SELECT * FROM receivable_titles WHERE id=?", (title_id,)).fetchone()
        if not t:
            raise ValueError("Título não encontrado")
        if amount <= 0 or amount > t["balance"]:
            raise ValueError("Valor inválido")
        new_balance = t["balance"] - amount
        status = "paid" if new_balance == 0 else "partial"
        conn.execute("UPDATE receivable_titles SET balance=?, status=?, payment_method_id=?, updated_at=? WHERE id=?", (new_balance, status, payment_method_id, now_iso(), title_id))
        conn.execute(
            "INSERT INTO receivable_events(title_id,action,amount,payment_method_id,reason,created_at) VALUES(?,?,?,?,?,?)",
            (title_id, "settled", amount, payment_method_id, None, now_iso()),
        )
        row = conn.execute("SELECT * FROM receivable_titles WHERE id=?", (title_id,)).fetchone()
        audit(conn, user, "settle", "receivable_titles", str(title_id), self.client_address[0], before=row_to_dict(t), after=row_to_dict(row))
        conn.commit()
        send_json(self, 200, row_to_dict(row))

    def reopen_title(self, conn, user, title_id):
        data = parse_json(self)
        amount = float(data["amount"])
        reason = str(data.get("reason", "")).strip()
        if amount <= 0 or not reason:
            raise ValueError("Motivo e valor são obrigatórios")
        t = conn.execute("SELECT * FROM receivable_titles WHERE id=?", (title_id,)).fetchone()
        if not t:
            raise ValueError("Título não encontrado")
        new_balance = t["balance"] + amount
        conn.execute("UPDATE receivable_titles SET balance=?, status='open', updated_at=? WHERE id=?", (new_balance, now_iso(), title_id))
        conn.execute(
            "INSERT INTO receivable_events(title_id,action,amount,payment_method_id,reason,created_at) VALUES(?,?,?,?,?,?)",
            (title_id, "reopened", amount, None, reason, now_iso()),
        )
        row = conn.execute("SELECT * FROM receivable_titles WHERE id=?", (title_id,)).fetchone()
        audit(conn, user, "reopen", "receivable_titles", str(title_id), self.client_address[0], before=row_to_dict(t), after=row_to_dict(row))
        conn.commit()
        send_json(self, 200, row_to_dict(row))

    def report_receivables(self, conn, query):
        start = query.get("start", [None])[0]
        end = query.get("end", [None])[0]
        status = query.get("status", [None])[0]
        where = "WHERE 1=1"
        args = []
        if start:
            where += " AND due_date>=?"
            args.append(start)
        if end:
            where += " AND due_date<=?"
            args.append(end)
        if status:
            where += " AND status=?"
            args.append(status)
        rows = conn.execute(f"SELECT * FROM receivable_titles {where} ORDER BY due_date", args).fetchall()
        html_rows = "".join([f"<tr><td>{r['id']}</td><td>{r['order_id']}</td><td>{r['due_date']}</td><td>{r['status']}</td><td>{r['balance']:.2f}</td></tr>" for r in rows])
        html = f"""
        <html><head><style>
        @page {{ size: A4; margin: 10mm; }}
        table {{ width:100%; border-collapse:collapse; page-break-inside:auto; }}
        tr {{ page-break-inside:avoid; page-break-after:auto; }}
        th,td {{ border:1px solid #333; padding:6px; }}
        </style></head><body>
        <h1>Relatório A4 - Contas a Receber</h1>
        <table><thead><tr><th>ID</th><th>Pedido</th><th>Vencimento</th><th>Status</th><th>Saldo</th></tr></thead><tbody>{html_rows}</tbody></table>
        </body></html>
        """
        send_json(self, 200, {"html": html, "pdf_url": f"/reports/receivables?{urlparse(self.path).query}"})

    def report_orders(self, conn, query):
        status = query.get("status", [None])[0]
        where, args = "WHERE 1=1", []
        if status:
            where += " AND status=?"
            args.append(status)
        rows = conn.execute(f"SELECT * FROM orders {where} ORDER BY created_at DESC", args).fetchall()
        send_json(self, 200, {"items": [row_to_dict(r) for r in rows], "print_css": "@page { size:A4; }"})

    def upload_product_image(self, conn, user):
        data = parse_json(self)
        product_id = int(data["product_id"])
        content_type = data.get("content_type", "")
        b64 = data.get("content_b64", "")
        if content_type not in ("image/png", "image/jpeg"):
            raise ValueError("Tipo inválido")
        raw = base64.b64decode(b64)
        if len(raw) > 2_000_000:
            raise ValueError("Arquivo muito grande")
        h = hashlib.sha256(raw).hexdigest()[:16]
        ext = ".png" if content_type == "image/png" else ".jpg"
        filename = f"product-{product_id}-{h}{ext}"
        fpath = UPLOAD_DIR / filename
        fpath.write_bytes(raw)
        conn.execute("UPDATE products SET image_url=?, updated_at=? WHERE id=?", (str(fpath), now_iso(), product_id))
        row = conn.execute("SELECT * FROM products WHERE id=?", (product_id,)).fetchone()
        if not row:
            raise ValueError("Produto não encontrado")
        audit(conn, user, "upload", "products", str(product_id), self.client_address[0], after={"image_url": str(fpath)})
        conn.commit()
        send_json(self, 200, {"url": str(fpath)})

    def upload_company_logo(self, conn, user):
        data = parse_json(self)
        content_type = data.get("content_type", "")
        b64 = data.get("content_b64", "")
        if content_type not in ("image/png", "image/jpeg"):
            raise ValueError("Tipo inválido")
        raw = base64.b64decode(b64)
        if len(raw) > 2_000_000:
            raise ValueError("Arquivo muito grande")
        h = hashlib.sha256(raw).hexdigest()[:16]
        ext = ".png" if content_type == "image/png" else ".jpg"
        fpath = UPLOAD_DIR / f"logo-{h}{ext}"
        fpath.write_bytes(raw)
        conn.execute("UPDATE company_config SET logo_url=?, updated_at=? WHERE id=1", (str(fpath), now_iso()))
        audit(conn, user, "upload", "company_config", "1", self.client_address[0], after={"logo_url": str(fpath)})
        conn.commit()
        send_json(self, 200, {"url": str(fpath)})

    def list_audits(self, conn, query):
        user = query.get("user", [None])[0]
        entity = query.get("entity", [None])[0]
        start = query.get("start", [None])[0]
        end = query.get("end", [None])[0]
        where = "WHERE 1=1"
        args = []
        if user:
            where += " AND user_name=?"
            args.append(user)
        if entity:
            where += " AND entity=?"
            args.append(entity)
        if start:
            where += " AND created_at>=?"
            args.append(start)
        if end:
            where += " AND created_at<=?"
            args.append(end)
        rows = conn.execute(f"SELECT * FROM audits {where} ORDER BY id DESC", args).fetchall()
        send_json(self, 200, {"items": [row_to_dict(r) for r in rows]})

    def login(self):
        data = parse_json(self)
        username = data.get("username", "")
        password = data.get("password", "")
        ip = self.client_address[0]
        with LOCK:
            now = time.time()
            window = 600
            attempts = [t for t in STATE.login_attempts.get(ip, []) if now - t <= window]
            if len(attempts) >= 5:
                send_json(self, 429, {"error": "Muitas tentativas. Bloqueio temporário ativo"})
                return
            if password != "admin":
                attempts.append(now)
                STATE.login_attempts[ip] = attempts
                send_json(self, 401, {"error": "Credenciais inválidas"})
                return
            token = uuid4().hex
            STATE.csrf_tokens[username] = token
            STATE.login_attempts[ip] = []
        send_json(self, 200, {"token": f"fake-{username}", "csrf_token": token})


def run_server(host="127.0.0.1", port=8000):
    apply_migrations()
    server = ThreadingHTTPServer((host, port), Handler)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()


if __name__ == "__main__":
    run_server()
