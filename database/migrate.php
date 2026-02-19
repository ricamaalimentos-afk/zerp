<?php
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Database;

$pdo = Database::conn();
$sql = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($sql);
echo "Migrations applied\n";
