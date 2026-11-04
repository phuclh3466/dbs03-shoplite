<?php
require 'db.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');

$c = db();
$q = $_GET['q'] ?? '';

// ===== LO HONG: SQL Injection - UNION-based qua API an (CWE-89) =====
// Khac voi cac truy van khac trong index.php (dung PDO::prepare + tham so),
// endpoint goi y tim kiem nay noi chuoi $q TRUC TIEP vao cau lenh SQL.
// SQLite khong co stored procedure/stacked query mac dinh qua PDO::query(),
// nhung van cho phep UNION SELECT -> co the doc du lieu tu bang "secrets".
if (strlen($q) > 150) {
    echo '[]';
    exit;
}

// Co lap pham vi Flag #4: cho phep do schema qua sqlite_master de tim ra bang "secrets",
// nhung chan truy cap cheo sang bang users, orders va cac bang flag_level1/2/3/5.
if (preg_match('/(users|orders|flag_level1|flag_level2|flag_level3|flag_level5)/i', $q)) {
    echo '[]';
    exit;
}

$sql = "SELECT id, name, price FROM products WHERE name LIKE '%" . $q . "%' ORDER BY id ASC LIMIT 8";

try {
    $rows = $c->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);