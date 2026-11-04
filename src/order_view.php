<?php
require 'db.php';
require_login();

$c = db();
$id = (int)($_GET['id'] ?? 0);

// DA VA (bai nay chi tap trung vao SQL Injection nen khong con giu loi IDOR):
// bat buoc don hang phai thuoc ve dung user dang dang nhap.
$stmt = $c->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$id, $_SESSION['uid']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

layout_head('ShopLite - Chi tiết đơn hàng');
?>
<div style="margin-bottom:14px">
  <a class="btn btn-secondary btn-sm" href="profile.php">← Quay lại Hồ sơ & Đơn hàng</a>
</div>

<?php if (!$order): ?>
  <div class="card"><p class="err">Không tìm thấy đơn hàng.</p></div>
<?php else: ?>
  <div class="card">
    <h1 style="margin-bottom:4px">Đơn hàng #<?php echo (int)$order['id']; ?></h1>
    <p class="muted" style="margin-top:0">Đặt bởi tài khoản: <strong><?php echo htmlspecialchars($order['username']); ?></strong> · <?php echo htmlspecialchars($order['created_at']); ?></p>

    <div class="grid-2" style="margin-top:14px">
      <div>
        <h3>Thông tin nhận hàng</h3>
        <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['recipient_name']); ?></p>
        <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
        <?php if (!empty($order['note'])): ?>
          <p><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['note']); ?></p>
        <?php endif; ?>
      </div>
      <div>
        <h3>Thanh toán</h3>
        <p><strong>Phương thức:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <p><strong>Trạng thái:</strong> <?php echo status_badge($order['status'] ?? 'Chờ xử lý'); ?></p>
        <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total']); ?> VND</p>
      </div>
    </div>

    <h3 style="margin-top:18px">Sản phẩm</h3>
    <?php $its = json_decode($order['items'], true) ?: []; ?>
    <ul>
      <?php foreach ($its as $it): ?>
        <li><?php echo htmlspecialchars($it['name'] ?? ''); ?> × <?php echo (int)($it['qty'] ?? 1); ?> — <?php echo number_format((int)($it['price'] ?? 0)); ?>đ</li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>
