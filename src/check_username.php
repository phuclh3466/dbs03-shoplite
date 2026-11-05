<?php
require 'db.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
$c = db();

$u = $_GET['u'] ?? '';

if (strlen($u) > 200) {
    echo json_encode(['taken' => false]);
    exit;
}

// ===== LO HONG: SQL Injection - Boolean-based Blind (CWE-89) =====
// Endpoint nay chi tra ve true/false ("taken") de bao xem username da ton
// tai hay chua khi nguoi dung dang go o form dang ky (khong hien thi du
// lieu truc tiep nhu UNION-based). $u noi chuoi truc tiep vao SQL ->
// ke tan cong co the chen dieu kien boolean (AND/OR ...) va suy luan tung
// bit/tung ky tu du lieu dua vao true/false nhan duoc (vd dung sqlmap voi
// ky thuat --technique=B, hoac script tu viet).
// Co lap pham vi Flag #3: cho phep do sqlite_master va doc bang flag_level3,
// chan doc mat khau user hoac cac bang flag cua cap do khac.
if (preg_match('/(password|orders|flag_level1|flag_level2|flag_level5|secrets)/i', $u)) {
    echo json_encode(['taken' => false]);
    exit;
}

$sql = "SELECT COUNT(*) FROM users WHERE username = '" . $u . "'";
try {
    $taken = ((int) $c->query($sql)->fetchColumn()) > 0;
} catch (Throwable $e) {
    $taken = false;
}

echo json_encode(['taken' => $taken]);
