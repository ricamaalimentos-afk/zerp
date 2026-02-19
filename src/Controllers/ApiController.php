<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Http;
use PDO;

final class ApiController
{
    public function handle(string $method, string $path): void
    {
        $pdo = Database::conn();
        if ($path === '/health' && $method === 'GET') {
            Http::json(['ok' => true]);
            return;
        }
        if ($path === '/auth/login' && $method === 'POST') {
            $this->login($pdo);
            return;
        }

        if ($method !== 'GET') {
            $this->requireCsrf($pdo);
        }

        if ($path === '/customer-groups') {
            $this->crudSimple($pdo, 'customer_groups', $method, ['name', 'code']); return;
        }
        if ($path === '/payment-methods') {
            $this->crudSimple($pdo, 'payment_methods', $method, ['name', 'code']); return;
        }
        if ($path === '/products') {
            $this->crudProducts($pdo, $method); return;
        }
        if ($path === '/price-tables') {
            $this->crudPriceTables($pdo, $method); return;
        }
        if (preg_match('#^/customer-groups/(\d+)$#', $path, $m)) { $this->crudSimpleById($pdo, 'customer_groups', (int)$m[1], $method, ['name','code','active'], 'orders', 'customer_group_id'); return; }
        if (preg_match('#^/payment-methods/(\d+)$#', $path, $m)) { $this->crudSimpleById($pdo, 'payment_methods', (int)$m[1], $method, ['name','code','active'], 'receivable_titles', 'payment_method_id'); return; }
        if (preg_match('#^/price-tables/(\d+)$#', $path, $m)) { $this->crudSimpleById($pdo, 'price_tables', (int)$m[1], $method, ['name','customer_group_id','product_id','price','active']); return; }

        if ($path === '/orders' && $method === 'POST') { $this->createOrder($pdo); return; }
        if ($path === '/orders' && $method === 'GET') { $this->listOrders($pdo); return; }
        if (preg_match('#^/orders/(\d+)$#', $path, $m) && $method === 'PUT') { $this->updateOrder($pdo, (int)$m[1]); return; }
        if (preg_match('#^/orders/(\d+)/items$#', $path, $m) && $method === 'POST') { $this->addOrderItem($pdo, (int)$m[1]); return; }
        if (preg_match('#^/orders/(\d+)/items/(\d+)$#', $path, $m) && $method === 'PUT') { $this->updateOrderItem($pdo, (int)$m[1], (int)$m[2]); return; }
        if (preg_match('#^/orders/(\d+)/items/(\d+)$#', $path, $m) && $method === 'DELETE') { $this->deleteOrderItem($pdo, (int)$m[1], (int)$m[2]); return; }

        if (preg_match('#^/orders/(\d+)/generate-title$#', $path, $m) && $method === 'POST') { $this->generateTitle($pdo, (int)$m[1]); return; }
        if (preg_match('#^/receivables/(\d+)/settle$#', $path, $m) && $method === 'POST') { $this->settleTitle($pdo, (int)$m[1]); return; }
        if (preg_match('#^/receivables/(\d+)/reopen$#', $path, $m) && $method === 'POST') { $this->reopenTitle($pdo, (int)$m[1]); return; }

        if ($path === '/reports/receivables' && $method === 'GET') { $this->reportReceivables($pdo); return; }
        if ($path === '/reports/orders' && $method === 'GET') { $this->reportOrders($pdo); return; }
        if ($path === '/audits' && $method === 'GET') { $this->listAudits($pdo); return; }
        if ($path === '/upload/product-image' && $method === 'POST') { $this->uploadProductImage($pdo); return; }
        if ($path === '/upload/company-logo' && $method === 'POST') { $this->uploadCompanyLogo($pdo); return; }

        Http::json(['error' => 'Rota não encontrada'], 404);
    }

    private function login(PDO $pdo): void {
        $b = Http::body(); $ip = Http::ip();
        $attempts = (int)$pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip=? AND attempted_at >= (NOW() - INTERVAL 10 MINUTE)")->execute([$ip]) ?: 0;
        $stmt = $pdo->prepare("SELECT COUNT(*) c FROM login_attempts WHERE ip=? AND attempted_at >= (NOW() - INTERVAL 10 MINUTE)");
        $stmt->execute([$ip]);
        if ((int)$stmt->fetchColumn() >= 5) { Http::json(['error'=>'Muitas tentativas. Bloqueio temporário ativo'],429); return; }
        if (($b['password'] ?? '') !== 'admin') {
            $pdo->prepare('INSERT INTO login_attempts(ip,attempted_at) VALUES(?,NOW())')->execute([$ip]);
            Http::json(['error'=>'Credenciais inválidas'],401); return;
        }
        $csrf = bin2hex(random_bytes(16));
        $user = (string)($b['username'] ?? 'system');
        $pdo->prepare('INSERT INTO auth_sessions(user_name,csrf_token,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE csrf_token=VALUES(csrf_token), updated_at=NOW()')->execute([$user,$csrf]);
        Http::json(['token'=>'fake-'.$user, 'csrf_token'=>$csrf]);
    }

    private function requireCsrf(PDO $pdo): void {
        $user = Http::user(); $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$user || !$token) { Http::json(['error'=>'CSRF token inválido'],403); exit; }
        $st = $pdo->prepare('SELECT csrf_token FROM auth_sessions WHERE user_name=?'); $st->execute([$user]);
        if (($st->fetchColumn() ?: '') !== $token) { Http::json(['error'=>'CSRF token inválido'],403); exit; }
    }

    private function crudSimple(PDO $pdo, string $table, string $method, array $required): void {
        if ($method === 'GET') {
            $q = strtolower($_GET['q'] ?? ''); $page=max(1,(int)($_GET['page']??1)); $per=min(100,max(1,(int)($_GET['per_page']??20))); $off=($page-1)*$per;
            $where=''; $params=[];
            if ($q!=='') { $where=' WHERE LOWER(name) LIKE ? OR LOWER(code) LIKE ?'; $params=["%$q%","%$q%"]; }
            $c=$pdo->prepare("SELECT COUNT(*) FROM {$table}{$where}"); $c->execute($params); $total=(int)$c->fetchColumn();
            $s=$pdo->prepare("SELECT * FROM {$table}{$where} ORDER BY id LIMIT {$per} OFFSET {$off}"); $s->execute($params);
            Http::json(['items'=>$s->fetchAll(),'page'=>$page,'per_page'=>$per,'total'=>$total]); return;
        }
        if ($method === 'POST') {
            $b=Http::body(); foreach($required as $f){ if(empty($b[$f])){ Http::json(['error'=>"{$f} é obrigatório"],422); return; }}
            $pdo->prepare("INSERT INTO {$table}(name,code,active,created_at,updated_at) VALUES(?,?,?,NOW(),NOW())")->execute([$b['name'],$b['code'],(int)($b['active']??1)]);
            $id=(int)$pdo->lastInsertId(); $r=$pdo->query("SELECT * FROM {$table} WHERE id={$id}")->fetch();
            $this->audit($pdo,'create',$table,(string)$id,null,$r);
            Http::json($r,201); return;
        }
        Http::json(['error'=>'Método não permitido'],405);
    }

    private function crudSimpleById(PDO $pdo,string $table,int $id,string $method,array $fields,?string $usageTable=null,?string $usageField=null): void {
        if ($method==='PUT') {
            $b=Http::body(); $sets=[]; $vals=[];
            foreach($fields as $f){ if(array_key_exists($f,$b)){ $sets[]="$f=?"; $vals[]=$b[$f]; }}
            if(!$sets){ Http::json(['error'=>'Nada para atualizar'],422); return; }
            $before=$pdo->query("SELECT * FROM {$table} WHERE id={$id}")->fetch();
            $vals[]=$id; $sql='UPDATE '.$table.' SET '.implode(',', $sets).', updated_at=NOW() WHERE id=?';
            $pdo->prepare($sql)->execute($vals);
            $after=$pdo->query("SELECT * FROM {$table} WHERE id={$id}")->fetch();
            $this->audit($pdo,'update',$table,(string)$id,$before,$after);
            Http::json($after); return;
        }
        if ($method==='DELETE') {
            if ($usageTable && $usageField) {
                $st=$pdo->prepare("SELECT COUNT(*) FROM {$usageTable} WHERE {$usageField}=?"); $st->execute([$id]);
                if ((int)$st->fetchColumn()>0) { Http::json(['error'=>'Registro em uso; exclusão não permitida'],422); return; }
            }
            $before=$pdo->query("SELECT * FROM {$table} WHERE id={$id}")->fetch();
            $pdo->prepare("UPDATE {$table} SET active=0, updated_at=NOW() WHERE id=?")->execute([$id]);
            $after=$pdo->query("SELECT * FROM {$table} WHERE id={$id}")->fetch();
            $this->audit($pdo,'soft_delete',$table,(string)$id,$before,$after);
            Http::json(['ok'=>true]); return;
        }
        Http::json(['error'=>'Método não permitido'],405);
    }

    private function crudProducts(PDO $pdo,string $method): void {
        if($method==='GET'){ $s=$pdo->query('SELECT * FROM products ORDER BY id'); Http::json(['items'=>$s->fetchAll()]); return; }
        if($method==='POST'){ $b=Http::body(); $pdo->prepare('INSERT INTO products(code,name,base_price,stock,image_url,created_at,updated_at) VALUES(?,?,?,?,NULL,NOW(),NOW())')->execute([$b['code'],$b['name'],$b['base_price'],$b['stock']??0]); Http::json(['id'=>(int)$pdo->lastInsertId()],201); return; }
        Http::json(['error'=>'Método não permitido'],405);
    }

    private function crudPriceTables(PDO $pdo,string $method): void {
        if($method==='GET'){ $s=$pdo->query('SELECT * FROM price_tables ORDER BY id'); Http::json(['items'=>$s->fetchAll()]); return; }
        if($method==='POST'){ $b=Http::body(); $pdo->prepare('INSERT INTO price_tables(name,customer_group_id,product_id,price,active,created_at,updated_at) VALUES(?,?,?,?,1,NOW(),NOW())')->execute([$b['name'],$b['customer_group_id'],$b['product_id'],$b['price']]); Http::json(['id'=>(int)$pdo->lastInsertId()],201); return; }
        Http::json(['error'=>'Método não permitido'],405);
    }

    private function resolvePrice(PDO $pdo,int $groupId,int $productId,?float $negotiated): float {
        if($negotiated!==null){ return $negotiated; }
        $s=$pdo->prepare('SELECT price FROM price_tables WHERE active=1 AND customer_group_id=? AND product_id=? ORDER BY id DESC LIMIT 1'); $s->execute([$groupId,$productId]); $p=$s->fetchColumn();
        if($p!==false){ return (float)$p; }
        $s=$pdo->prepare('SELECT base_price FROM products WHERE id=?'); $s->execute([$productId]); return (float)$s->fetchColumn();
    }

    private function recalcOrder(PDO $pdo,int $orderId): void {
        $subtotal=(float)$pdo->query("SELECT COALESCE(SUM(line_total),0) FROM order_items WHERE order_id={$orderId}")->fetchColumn();
        $discount=(float)$pdo->query("SELECT discount FROM orders WHERE id={$orderId}")->fetchColumn();
        $total=max(0,$subtotal-$discount);
        $pdo->prepare('UPDATE orders SET subtotal=?, total=?, updated_at=NOW() WHERE id=?')->execute([$subtotal,$total,$orderId]);
    }

    private function createOrder(PDO $pdo): void {
        $b=Http::body();
        $pdo->prepare("INSERT INTO orders(customer_name,customer_group_id,status,subtotal,discount,total,created_at,updated_at) VALUES(?,?,'draft',0,?,0,NOW(),NOW())")->execute([$b['customer_name'],$b['customer_group_id'],$b['discount']??0]);
        $orderId=(int)$pdo->lastInsertId();
        foreach(($b['items']??[]) as $it){ $this->saveOrderItem($pdo,$orderId,$it,null); }
        $this->recalcOrder($pdo,$orderId);
        Http::json($pdo->query("SELECT * FROM orders WHERE id={$orderId}")->fetch(),201);
    }

    private function saveOrderItem(PDO $pdo,int $orderId,array $it,?int $itemId): void {
        $stock=(int)$pdo->query('SELECT stock FROM products WHERE id='.(int)$it['product_id'])->fetchColumn();
        if($stock < (int)$it['quantity']){ throw new \RuntimeException('Estoque insuficiente'); }
        $group=(int)$pdo->query("SELECT customer_group_id FROM orders WHERE id={$orderId}")->fetchColumn();
        $neg=isset($it['negotiated_price']) ? (float)$it['negotiated_price'] : null;
        $unit=$this->resolvePrice($pdo,$group,(int)$it['product_id'],$neg); $line=$unit*(int)$it['quantity'];
        if($itemId===null){
            $pdo->prepare('INSERT INTO order_items(order_id,product_id,quantity,negotiated_price,unit_price,line_total,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())')->execute([$orderId,$it['product_id'],$it['quantity'],$neg,$unit,$line]);
        } else {
            $pdo->prepare('UPDATE order_items SET product_id=?, quantity=?, negotiated_price=?, unit_price=?, line_total=?, updated_at=NOW() WHERE id=? AND order_id=?')->execute([$it['product_id'],$it['quantity'],$neg,$unit,$line,$itemId,$orderId]);
        }
    }

    private function addOrderItem(PDO $pdo,int $orderId): void { $this->saveOrderItem($pdo,$orderId,Http::body(),null); $this->recalcOrder($pdo,$orderId); Http::json(['ok'=>true],201); }
    private function updateOrderItem(PDO $pdo,int $orderId,int $itemId): void {
        $before=$pdo->query("SELECT * FROM order_items WHERE id={$itemId}")->fetch();
        $this->saveOrderItem($pdo,$orderId,Http::body(),$itemId); $this->recalcOrder($pdo,$orderId);
        $after=$pdo->query("SELECT * FROM order_items WHERE id={$itemId}")->fetch();
        if(($before['negotiated_price']??null)!==($after['negotiated_price']??null)){ $this->audit($pdo,'manual_price_change','order_items',(string)$itemId,$before,$after); }
        Http::json($after);
    }
    private function deleteOrderItem(PDO $pdo,int $orderId,int $itemId): void { $pdo->prepare('DELETE FROM order_items WHERE id=? AND order_id=?')->execute([$itemId,$orderId]); $this->recalcOrder($pdo,$orderId); Http::json(['ok'=>true]); }
    private function updateOrder(PDO $pdo,int $orderId): void {
        $b=Http::body(); $pdo->prepare('UPDATE orders SET status=?, discount=?, updated_at=NOW() WHERE id=?')->execute([$b['status']??'draft',$b['discount']??0,$orderId]); $this->recalcOrder($pdo,$orderId); Http::json($pdo->query("SELECT * FROM orders WHERE id={$orderId}")->fetch());
    }
    private function listOrders(PDO $pdo): void { Http::json(['items'=>$pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll()]); }

    private function generateTitle(PDO $pdo,int $orderId): void {
        $b=Http::body(); $o=$pdo->query("SELECT * FROM orders WHERE id={$orderId}")->fetch(); if(!$o || $o['status']!=='faturado'){ Http::json(['error'=>'Pedido precisa estar faturado'],422); return; }
        $due=(new \DateTime('+'.(int)($b['due_days']??30).' day'))->format('Y-m-d');
        $pdo->prepare("INSERT INTO receivable_titles(order_id,payment_method_id,due_date,amount,balance,status,created_at,updated_at) VALUES(?,?,?,?,?,'open',NOW(),NOW())")->execute([$orderId,$b['payment_method_id']??null,$due,$o['total'],$o['total']]);
        $id=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO receivable_events(title_id,action,amount,payment_method_id,reason,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$id,'generated',$o['total'],$b['payment_method_id']??null,null]);
        Http::json($pdo->query("SELECT * FROM receivable_titles WHERE id={$id}")->fetch(),201);
    }

    private function settleTitle(PDO $pdo,int $titleId): void {
        $b=Http::body(); $t=$pdo->query("SELECT * FROM receivable_titles WHERE id={$titleId}")->fetch(); $amt=(float)$b['amount'];
        if($amt<=0 || $amt>(float)$t['balance']){ Http::json(['error'=>'Valor inválido'],422); return; }
        $balance=(float)$t['balance']-$amt; $status=$balance==0.0?'paid':'partial';
        $pdo->prepare('UPDATE receivable_titles SET balance=?, status=?, payment_method_id=?, updated_at=NOW() WHERE id=?')->execute([$balance,$status,$b['payment_method_id']??null,$titleId]);
        $pdo->prepare('INSERT INTO receivable_events(title_id,action,amount,payment_method_id,reason,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$titleId,'settled',$amt,$b['payment_method_id']??null,null]);
        Http::json($pdo->query("SELECT * FROM receivable_titles WHERE id={$titleId}")->fetch());
    }

    private function reopenTitle(PDO $pdo,int $titleId): void {
        $b=Http::body(); if(empty($b['reason']) || (float)$b['amount']<=0){ Http::json(['error'=>'Motivo e valor obrigatórios'],422); return; }
        $t=$pdo->query("SELECT * FROM receivable_titles WHERE id={$titleId}")->fetch(); $balance=(float)$t['balance']+(float)$b['amount'];
        $pdo->prepare("UPDATE receivable_titles SET balance=?, status='open', updated_at=NOW() WHERE id=?")->execute([$balance,$titleId]);
        $pdo->prepare('INSERT INTO receivable_events(title_id,action,amount,payment_method_id,reason,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$titleId,'reopened',$b['amount'],null,$b['reason']]);
        Http::json($pdo->query("SELECT * FROM receivable_titles WHERE id={$titleId}")->fetch());
    }

    private function reportReceivables(PDO $pdo): void {
        $where=' WHERE 1=1'; $args=[];
        if(!empty($_GET['start'])){ $where.=' AND due_date >= ?'; $args[]=$_GET['start']; }
        if(!empty($_GET['end'])){ $where.=' AND due_date <= ?'; $args[]=$_GET['end']; }
        if(!empty($_GET['status'])){ $where.=' AND status = ?'; $args[]=$_GET['status']; }
        $st=$pdo->prepare('SELECT * FROM receivable_titles'.$where.' ORDER BY due_date'); $st->execute($args); $rows=$st->fetchAll();
        $trs=''; foreach($rows as $r){ $trs.="<tr><td>{$r['id']}</td><td>{$r['order_id']}</td><td>{$r['due_date']}</td><td>{$r['status']}</td><td>{$r['balance']}</td></tr>"; }
        $html="<style>@page{size:A4;margin:10mm}table{width:100%;border-collapse:collapse}th,td{border:1px solid #333;padding:6px}tr{page-break-inside:avoid}</style><h1>Relatório A4 - Contas a Receber</h1><table><tr><th>ID</th><th>Pedido</th><th>Vencimento</th><th>Status</th><th>Saldo</th></tr>{$trs}</table>";
        Http::json(['html'=>$html, 'pdf_url'=>'/reports/receivables?'.http_build_query($_GET)]);
    }

    private function reportOrders(PDO $pdo): void {
        $status=$_GET['status']??null;
        if($status){ $s=$pdo->prepare('SELECT * FROM orders WHERE status=? ORDER BY id DESC'); $s->execute([$status]); $rows=$s->fetchAll(); }
        else { $rows=$pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll(); }
        Http::json(['items'=>$rows,'print_css'=>'@page { size: A4; }']);
    }

    private function uploadProductImage(PDO $pdo): void {
        $b=Http::body(); $raw=base64_decode($b['content_b64']??'', true); if($raw===false){ Http::json(['error'=>'Base64 inválido'],422); return; }
        if(!in_array($b['content_type']??'', ['image/png','image/jpeg'], true) || strlen($raw)>2_000_000){ Http::json(['error'=>'Arquivo inválido'],422); return; }
        $ext=($b['content_type']==='image/png')?'.png':'.jpg'; $hash=substr(hash('sha256',$raw),0,16); $file='uploads/product-'.(int)$b['product_id'].'-'.$hash.$ext;
        if(!is_dir('uploads')){ mkdir('uploads',0777,true);} file_put_contents($file,$raw);
        $pdo->prepare('UPDATE products SET image_url=?, updated_at=NOW() WHERE id=?')->execute([$file,(int)$b['product_id']]);
        Http::json(['url'=>$file]);
    }

    private function uploadCompanyLogo(PDO $pdo): void {
        $b=Http::body(); $raw=base64_decode($b['content_b64']??'', true); if($raw===false){ Http::json(['error'=>'Base64 inválido'],422); return; }
        if(!in_array($b['content_type']??'', ['image/png','image/jpeg'], true) || strlen($raw)>2_000_000){ Http::json(['error'=>'Arquivo inválido'],422); return; }
        $ext=($b['content_type']==='image/png')?'.png':'.jpg'; $hash=substr(hash('sha256',$raw),0,16); $file='uploads/logo-'.$hash.$ext;
        if(!is_dir('uploads')){ mkdir('uploads',0777,true);} file_put_contents($file,$raw);
        $pdo->prepare('UPDATE company_config SET logo_url=?, updated_at=NOW() WHERE id=1')->execute([$file]);
        Http::json(['url'=>$file]);
    }

    private function listAudits(PDO $pdo): void {
        $where=' WHERE 1=1'; $args=[];
        foreach(['user_name'=>'user','entity'=>'entity'] as $col=>$q){ if(!empty($_GET[$q])){ $where.=" AND {$col}=?"; $args[]=$_GET[$q]; }}
        if(!empty($_GET['start'])){ $where.=' AND created_at>=?'; $args[]=$_GET['start']; }
        if(!empty($_GET['end'])){ $where.=' AND created_at<=?'; $args[]=$_GET['end']; }
        $s=$pdo->prepare('SELECT * FROM audits'.$where.' ORDER BY id DESC'); $s->execute($args);
        Http::json(['items'=>$s->fetchAll()]);
    }

    private function audit(PDO $pdo,string $action,string $entity,string $entityId,$before,$after): void {
        $pdo->prepare('INSERT INTO audits(user_name,action,entity,entity_id,before_json,after_json,ip,created_at) VALUES(?,?,?,?,?,?,?,NOW())')
            ->execute([Http::user() ?: 'system',$action,$entity,$entityId,$before?json_encode($before):null,$after?json_encode($after):null,Http::ip()]);
    }
}
