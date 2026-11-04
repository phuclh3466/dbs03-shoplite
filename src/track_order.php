<?php
require 'db.php';
$c = db();

$code = $_GET['code'] ?? null;
$result = null;
$error = false;

if ($code !== null) {
    // ===== LO HONG: SQL Injection - UNION-based (CWE-89) =====
    // Tinh nang tra cuu don hang KHONG YEU CAU dang nhap, phuc vu khach
    // vang lai. $code duoc noi chuoi truc tiep vao cau SQL thay vi dung
    // prepared statement -> co the chen UNION SELECT de doc du lieu tu
    // bang bat ky, mien khop 3 cot (id, status, total).
    // Co lap pham vi Flag #2: cho phep do schema qua sqlite_master va doc bang flag_level2,
    // nhung chan doc cheo sang bang users hoac cac bang flag cua cap do khac.
    if (preg_match('/(users|flag_level1|flag_level3|flag_level5|secrets)/i', $code)) {
        $error = true;
    } else {
        $sql = "SELECT id, status, total FROM orders WHERE id = '" . $code . "' LIMIT 1";
        try {
            $result = $c->query($sql)->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $error = true;
        }
    }
}

layout_head('ShopLite - Tra cứu đơn hàng');
?>
<div class="card" style="max-width:560px;margin:0 auto">
  <h1>📦 Tra cứu đơn hàng</h1>
  <p class="muted">Nhập mã đơn hàng để kiểm tra trạng thái, không cần đăng nhập.</p>
  <form method="get">
    <label>Mã đơn hàng</label>
    <input type="text" name="code" value="<?php echo htmlspecialchars($code ?? ''); ?>" placeholder="Ví dụ: 5" required>
    <button type="submit" class="btn btn-block">Tra cứu</button>
  </form>

  <?php if ($error): ?>
    <p class="err" style="margin-top:14px">Mã đơn hàng không hợp lệ.</p>
  <?php elseif ($code !== null && !$result): ?>
    <p class="err" style="margin-top:14px">Không tìm thấy đơn hàng.</p>
  <?php elseif ($result): ?>
    <div style="margin-top:14px;border-top:1px solid var(--border);padding-top:14px">
      <p><strong>Mã đơn:</strong> #<?php echo htmlspecialchars($result['id']); ?></p>
      <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($result['status']); ?></p>
      <p><strong>Tổng tiền:</strong> <?php echo htmlspecialchars($result['total']); ?></p>
    </div>
  <?php endif; ?>
</div>
<?php layout_foot(); ?>