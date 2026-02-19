from __future__ import annotations

from dataclasses import dataclass, field, asdict
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Optional
from uuid import uuid4


@dataclass
class CustomerGroup:
    id: int
    name: str
    code: str
    active: bool = True


@dataclass
class PaymentMethod:
    id: int
    name: str
    code: str
    active: bool = True


@dataclass
class PriceTable:
    id: int
    name: str
    customer_group_id: int
    product_code: str
    price: float


@dataclass
class Product:
    id: int
    code: str
    name: str
    base_price: float
    image_url: Optional[str] = None


@dataclass
class OrderItem:
    id: int
    order_id: int
    product_code: str
    quantity: int
    negotiated_price: Optional[float]
    unit_price: float
    line_total: float


@dataclass
class SalesOrder:
    id: int
    customer_name: str
    customer_group_id: int
    discount: float
    subtotal: float
    total: float
    created_at: datetime = field(default_factory=datetime.utcnow)


@dataclass
class ReceivableTitle:
    id: int
    order_id: int
    due_date: date
    amount: float
    balance: float
    status: str = "open"


@dataclass
class ReceivableEvent:
    id: int
    title_id: int
    action: str
    amount: Optional[float] = None
    reason: Optional[str] = None
    created_at: datetime = field(default_factory=datetime.utcnow)


@dataclass
class AuditLog:
    id: int
    user: str
    action: str
    entity: str
    entity_id: int
    before: Optional[dict]
    after: Optional[dict]
    ip: str
    created_at: datetime = field(default_factory=datetime.utcnow)


class SecurityError(Exception):
    pass


class ValidationError(Exception):
    pass


class NotFoundError(Exception):
    pass


class RateLimitError(Exception):
    pass


class ZerpSystem:
    def __init__(self, upload_dir: str = "uploads"):
        self.upload_dir = Path(upload_dir)
        self.upload_dir.mkdir(exist_ok=True)

        self.customer_groups: dict[int, CustomerGroup] = {}
        self.payment_methods: dict[int, PaymentMethod] = {}
        self.price_tables: dict[int, PriceTable] = {}
        self.products: dict[int, Product] = {}
        self.orders: dict[int, SalesOrder] = {}
        self.order_items: dict[int, OrderItem] = {}
        self.receivables: dict[int, ReceivableTitle] = {}
        self.receivable_events: dict[int, ReceivableEvent] = {}
        self.audits: dict[int, AuditLog] = {}
        self.company_logo_url: Optional[str] = None

        self.counters = {k: 0 for k in [
            "customer_group", "payment_method", "price_table", "product",
            "order", "item", "title", "event", "audit"
        ]}

        self.csrf_tokens: dict[str, str] = {}
        self.login_attempts: dict[str, list[datetime]] = {}

    def _next(self, key: str) -> int:
        self.counters[key] += 1
        return self.counters[key]

    def _audit(self, *, user: str, action: str, entity: str, entity_id: int, ip: str = "127.0.0.1", before: dict | None = None, after: dict | None = None):
        aid = self._next("audit")
        self.audits[aid] = AuditLog(aid, user, action, entity, entity_id, before, after, ip)

    # Segurança
    def login(self, username: str, password: str, ip: str = "127.0.0.1") -> tuple[str, str]:
        now = datetime.utcnow()
        window = timedelta(minutes=10)
        attempts = [t for t in self.login_attempts.get(ip, []) if now - t < window]
        if len(attempts) >= 5:
            raise RateLimitError("Muitas tentativas de login")
        if password != "admin":
            attempts.append(now)
            self.login_attempts[ip] = attempts
            raise SecurityError("Credenciais inválidas")
        self.login_attempts[ip] = []
        csrf = uuid4().hex
        self.csrf_tokens[username] = csrf
        return f"fake-token-{username}", csrf

    def verify_csrf(self, username: str, token: str):
        if self.csrf_tokens.get(username) != token:
            raise SecurityError("CSRF token inválido")

    # CRUDs
    def create_customer_group(self, *, user: str, csrf: str, name: str, code: str, active: bool = True):
        self.verify_csrf(user, csrf)
        cid = self._next("customer_group")
        obj = CustomerGroup(cid, name, code, active)
        self.customer_groups[cid] = obj
        self._audit(user=user, action="create", entity="customer_group", entity_id=cid, after=asdict(obj))
        return obj

    def list_customer_groups(self, query: str | None = None):
        items = list(self.customer_groups.values())
        if query:
            q = query.lower()
            items = [i for i in items if q in i.name.lower() or q in i.code.lower()]
        return items

    def update_customer_group(self, *, user: str, csrf: str, group_id: int, name: str, code: str, active: bool):
        self.verify_csrf(user, csrf)
        obj = self.customer_groups.get(group_id)
        if not obj:
            raise NotFoundError
        before = asdict(obj)
        obj.name, obj.code, obj.active = name, code, active
        self._audit(user=user, action="update", entity="customer_group", entity_id=group_id, before=before, after=asdict(obj))
        return obj

    def delete_customer_group(self, *, user: str, csrf: str, group_id: int):
        self.verify_csrf(user, csrf)
        obj = self.customer_groups.get(group_id)
        if not obj:
            raise NotFoundError
        before = asdict(obj)
        obj.active = False
        self._audit(user=user, action="soft_delete", entity="customer_group", entity_id=group_id, before=before, after=asdict(obj))

    def create_payment_method(self, *, user: str, csrf: str, name: str, code: str, active: bool = True):
        self.verify_csrf(user, csrf)
        pid = self._next("payment_method")
        obj = PaymentMethod(pid, name, code, active)
        self.payment_methods[pid] = obj
        self._audit(user=user, action="create", entity="payment_method", entity_id=pid, after=asdict(obj))
        return obj

    def create_price_table(self, *, user: str, csrf: str, name: str, customer_group_id: int, product_code: str, price: float):
        self.verify_csrf(user, csrf)
        tid = self._next("price_table")
        obj = PriceTable(tid, name, customer_group_id, product_code, price)
        self.price_tables[tid] = obj
        self._audit(user=user, action="create", entity="price_table", entity_id=tid, after=asdict(obj))
        return obj

    def create_product(self, *, user: str, csrf: str, code: str, name: str, base_price: float):
        self.verify_csrf(user, csrf)
        pid = self._next("product")
        obj = Product(pid, code, name, base_price)
        self.products[pid] = obj
        self._audit(user=user, action="create", entity="product", entity_id=pid, after=asdict(obj))
        return obj

    # Pedidos
    def _resolve_price(self, customer_group_id: int, product_code: str, negotiated_price: Optional[float]) -> float:
        if negotiated_price is not None:
            return negotiated_price
        for t in self.price_tables.values():
            if t.customer_group_id == customer_group_id and t.product_code == product_code:
                return t.price
        for p in self.products.values():
            if p.code == product_code:
                return p.base_price
        raise NotFoundError("Produto não encontrado")

    def create_order(self, *, user: str, csrf: str, customer_name: str, customer_group_id: int, discount: float, items: list[dict]):
        self.verify_csrf(user, csrf)
        oid = self._next("order")
        subtotal = 0.0
        created_items = []
        for it in items:
            unit = self._resolve_price(customer_group_id, it["product_code"], it.get("negotiated_price"))
            line_total = unit * it["quantity"]
            subtotal += line_total
            iid = self._next("item")
            item = OrderItem(iid, oid, it["product_code"], it["quantity"], it.get("negotiated_price"), unit, line_total)
            self.order_items[iid] = item
            created_items.append(item)
        total = max(subtotal - discount, 0)
        order = SalesOrder(oid, customer_name, customer_group_id, discount, subtotal, total)
        self.orders[oid] = order
        self._audit(user=user, action="create", entity="order", entity_id=oid, after=asdict(order))
        return order, created_items

    # Contas a receber
    def generate_title(self, *, user: str, csrf: str, order_id: int, due_days: int = 30):
        self.verify_csrf(user, csrf)
        order = self.orders.get(order_id)
        if not order:
            raise NotFoundError
        tid = self._next("title")
        title = ReceivableTitle(tid, order_id, date.today() + timedelta(days=due_days), order.total, order.total)
        self.receivables[tid] = title
        eid = self._next("event")
        self.receivable_events[eid] = ReceivableEvent(eid, tid, "generated", amount=title.amount)
        self._audit(user=user, action="generate", entity="receivable", entity_id=tid, after=asdict(title))
        return title

    def settle_title(self, *, user: str, csrf: str, title_id: int, amount: float):
        self.verify_csrf(user, csrf)
        title = self.receivables.get(title_id)
        if not title:
            raise NotFoundError
        if amount <= 0 or amount > title.balance:
            raise ValidationError("Valor inválido")
        title.balance -= amount
        title.status = "paid" if title.balance == 0 else "partial"
        eid = self._next("event")
        self.receivable_events[eid] = ReceivableEvent(eid, title_id, "settled", amount=amount)
        self._audit(user=user, action="settle", entity="receivable", entity_id=title_id, after=asdict(title))
        return title

    def reopen_title(self, *, user: str, csrf: str, title_id: int, amount: float, reason: str):
        self.verify_csrf(user, csrf)
        title = self.receivables.get(title_id)
        if not title:
            raise NotFoundError
        if amount <= 0 or not reason.strip():
            raise ValidationError("Dados inválidos")
        title.balance += amount
        title.status = "open"
        eid = self._next("event")
        self.receivable_events[eid] = ReceivableEvent(eid, title_id, "reopened", amount=amount, reason=reason)
        self._audit(user=user, action="reopen", entity="receivable", entity_id=title_id, after=asdict(title))
        return title

    # Relatórios
    def receivables_report_a4(self, *, start: Optional[date] = None, end: Optional[date] = None, status: Optional[str] = None) -> str:
        titles = list(self.receivables.values())
        if start:
            titles = [t for t in titles if t.due_date >= start]
        if end:
            titles = [t for t in titles if t.due_date <= end]
        if status:
            titles = [t for t in titles if t.status == status]
        rows = "".join(f"<tr><td>{t.id}</td><td>{t.order_id}</td><td>{t.due_date}</td><td>{t.status}</td><td>{t.balance:.2f}</td></tr>" for t in titles)
        return (
            "<html><head><style>@page { size: A4; margin: 12mm; }"
            "table{width:100%;border-collapse:collapse} td,th{border:1px solid #333;padding:6px}"
            "</style></head><body><h1>Relatório A4</h1><table><tr><th>ID</th><th>Pedido</th><th>Vencimento</th><th>Status</th><th>Saldo</th></tr>"
            f"{rows}</table></body></html>"
        )

    # Upload
    def upload_product_image(self, *, user: str, csrf: str, product_code: str, filename: str, content_type: str, content: bytes):
        self.verify_csrf(user, csrf)
        if content_type not in {"image/png", "image/jpeg"}:
            raise ValidationError("Tipo inválido")
        if len(content) > 2_000_000:
            raise ValidationError("Arquivo grande")
        ext = ".png" if content_type == "image/png" else ".jpg"
        safe_name = f"product-{product_code}-{uuid4().hex}{ext}"
        path = self.upload_dir / safe_name
        path.write_bytes(content)
        target = next((p for p in self.products.values() if p.code == product_code), None)
        if not target:
            raise NotFoundError
        target.image_url = str(path)
        self._audit(user=user, action="upload", entity="product_image", entity_id=target.id, after={"image_url": target.image_url})
        return target.image_url

    def upload_logo(self, *, user: str, csrf: str, filename: str, content_type: str, content: bytes):
        self.verify_csrf(user, csrf)
        if content_type not in {"image/png", "image/jpeg"}:
            raise ValidationError("Tipo inválido")
        ext = ".png" if content_type == "image/png" else ".jpg"
        safe_name = f"logo-{uuid4().hex}{ext}"
        path = self.upload_dir / safe_name
        path.write_bytes(content)
        self.company_logo_url = str(path)
        self._audit(user=user, action="upload", entity="company_logo", entity_id=1, after={"logo_url": self.company_logo_url})
        return self.company_logo_url

    def list_audits(self, *, user: Optional[str] = None, entity: Optional[str] = None):
        logs = list(self.audits.values())
        if user:
            logs = [a for a in logs if a.user == user]
        if entity:
            logs = [a for a in logs if a.entity == entity]
        return logs
