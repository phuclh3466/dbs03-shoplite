<?php
require 'db.php';
require_login();

$c = db();
$msg = '';

// 1. Upload Avatar
// DA VA (bai nay chi tap trung vao SQL Injection nen khong con giu loi
// upload CWE-434 nua): khong tin Content-Type client tu khai, kiem tra
// noi dung that cua anh bang getimagesize(), va luon doi sang ten file
// ngau nhien + duoi an toan truoc khi luu, khong bao gio giu ten goc.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $f = $_FILES['avatar'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $allowedExt = ['jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'gif' => IMAGETYPE_GIF];
        $info = @getimagesize($f['tmp_name']);
        $detectedExt = null;
        if ($info !== false) {
            foreach ($allowedExt as $ext => $type) {
                if ($info[2] === $type) { $detectedExt = $ext; break; }
            }
        }
        if ($f['size'] > 0 && $f['size'] <= 5 * 1024 * 1024 && $detectedExt !== null) {
            $filename = bin2hex(random_bytes(16)) . '.' . $detectedExt;
            $dest = __DIR__ . '/uploads/' . $filename;
            if (move_uploaded_file($f['tmp_name'], $dest)) {
                $stmt = $c->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$filename, $_SESSION['uid']]);
                $msg = '<p class="ok">Cập nhật avatar thành công.</p>';
            } else {
                $msg = '<p class="err">Không thể lưu file.</p>';
            }
        } else {
            $msg = '<p class="err">Chỉ chấp nhận ảnh JPG/PNG/GIF hợp lệ, tối đa 5MB.</p>';
        }
    } else {
        $msg = '<p class="err">Lỗi khi upload file.</p>';
    }
}

// 2. Cap nhat thong tin ca nhan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $stmt = $c->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$fullName, $email, $phone, $address, $_SESSION['uid']]);
    $msg = '<p class="ok">Đã lưu thông tin hồ sơ cá nhân.</p>';
}

// 3. Doi mat khau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $chk = $c->prepare("SELECT password FROM users WHERE id = ?");
    $chk->execute([$_SESSION['uid']]);
    $curPass = $chk->fetchColumn();
    if ($oldPass !== $curPass) {
        $msg = '<p class="err">Mật khẩu hiện tại không chính xác.</p>';
    } elseif (strlen($newPass) < 4) {
        $msg = '<p class="err">Mật khẩu mới cần có ít nhất 4 ký tự.</p>';
    } else {
        $up = $c->prepare("UPDATE users SET password = ? WHERE id = ?");
        $up->execute([$newPass, $_SESSION['uid']]);
        $msg = '<p class="ok">Đổi mật khẩu thành công!</p>';
    }
}

// 4. Huy don hang dang cho xu ly
if (isset($_GET['cancel_order'])) {
    $oid = (int)$_GET['cancel_order'];
    $up = $c->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ? AND user_id = ? AND status = 'Chờ xử lý'");
    $up->execute([$oid, $_SESSION['uid']]);
    header('Location: profile.php?cancelled=1');
    exit;
}

$stmt = $c->prepare("SELECT username, role, full_name, email, phone, address, avatar, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['uid']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$oStmt = $c->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$oStmt->execute([$_SESSION['uid']]);
$myOrders = $oStmt->fetchAll(PDO::FETCH_ASSOC);

layout_head('ShopLite - Hồ sơ của tôi');
?>
<h1>Hồ sơ cá nhân & Đơn hàng</h1>

<?php if (!empty($_GET['cancelled'])): ?>
  <p class="ok">Đã hủy đơn hàng thành công.</p>
<?php endif; ?>
<?php echo $msg; ?>

<div class="grid-2" style="align-items:start">
  <!-- Cot trai: Avatar & Thong tin tai khoan -->
  <div>
    <div class="card" style="margin-top:0">
      <h3>Ảnh đại diện (Avatar)</h3>
      <div style="display:flex;align-items:center;gap:18px;margin-bottom:16px">
        <?php if (!empty($user['avatar'])): ?>
          <img class="avatar" src="uploads/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
        <?php else: ?>
          <div class="avatar-placeholder"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
        <?php endif; ?>
        <div>
          <div style="font-size:1.15rem;font-weight:700"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></div>
          <div class="muted">Tên đăng nhập: <strong><?php echo htmlspecialchars($user['username']); ?></strong></div>
          <div style="margin-top:4px">
            Vai trò: <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-info' : 'badge-neutral'; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
          </div>
        </div>
      </div>

      <form method="post" enctype="multipart/form-data">
        <label>Tải lên ảnh đại diện mới (JPG, PNG, GIF)</label>
        <input type="file" name="avatar" required>
        <button type="submit">📷 Cập nhật avatar</button>
      </form>
    </div>

    <div class="card">
      <h3>Đổi mật khẩu</h3>
      <form method="post">
        <input type="hidden" name="action" value="change_password">
        <label>Mật khẩu hiện tại</label>
        <input type="password" name="old_password" placeholder="Nhập mật khẩu đang dùng" required>
        <label>Mật khẩu mới</label>
        <input type="password" name="new_password" placeholder="Nhập mật khẩu mới" required>
        <button type="submit" class="btn btn-secondary">🔐 Cập nhật mật khẩu</button>
      </form>
    </div>
  </div>

  <!-- Cot phai: Cap nhat thong tin lien he -->
  <div class="card" style="margin-top:0">
    <h3>Thông tin liên hệ & Giao hàng mặc định</h3>
    <form method="post">
      <input type="hidden" name="action" value="update_info">
      <label>Họ và tên</label>
      <input name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Nguyễn Văn A">

      <label>Địa chỉ Email</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="email@example.com">

      <label>Số điện thoại</label>
      <input name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="09xxxxxxxx">

      <label>Địa chỉ nhận hàng</label>
      <textarea name="address" rows="3" placeholder="Số nhà, đường, quận/huyện, tỉnh/thành phố..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>

      <button type="submit">💾 Lưu thông tin</button>
    </form>
  </div>
</div>

<!-- Lich su don hang -->
<div class="card">
  <h3>🧾 Lịch sử mua sắm của tôi (<?php echo count($myOrders); ?> đơn)</h3>
  <p class="muted">Xem lại sản phẩm đã mua, số lượng, giá tại thời điểm đặt hàng, ngày đặt và trạng thái giao hàng.</p>
  <?php if (!$myOrders): ?>
    <p class="muted">Bạn chưa có đơn hàng nào. <a href="index.php">Khám phá sản phẩm ngay</a></p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Mã đơn</th>
            <th>Ngày đặt</th>
            <th>Sản phẩm</th>
            <th>Thanh toán</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myOrders as $od):
              $its = json_decode($od['items'], true) ?: [];
          ?>
            <tr>
              <td><strong>#<?php echo (int)$od['id']; ?></strong></td>
              <td class="muted"><?php echo htmlspecialchars($od['created_at']); ?></td>
              <td>
                <div class="order-items-list">
                <?php foreach ($its as $it):
                    $itemProduct = null;
                    if (!empty($it['id'])) {
                        $pStmt = $c->prepare("SELECT * FROM products WHERE id = ?");
                        $pStmt->execute([(int)$it['id']]);
                        $itemProduct = $pStmt->fetch(PDO::FETCH_ASSOC);
                    }
                ?>
                  <div class="order-item-row">
                    <div class="order-item-thumb">
                      <?php if ($itemProduct): echo render_product_thumb($itemProduct); else: ?><span>📦</span><?php endif; ?>
                    </div>
                    <div class="order-item-info">
                      <strong><?php echo htmlspecialchars($it['name'] ?? 'Sản phẩm'); ?></strong>
                      <span class="muted">Số lượng: ×<?php echo (int)($it['qty'] ?? 1); ?></span>
                      <span class="muted"><?php echo number_format((int)($it['price'] ?? 0)); ?> VND / sản phẩm</span>
                    </div>
                  </div>
                <?php endforeach; ?>
                </div>
                <?php if (!empty($od['address'])): ?>
                  <div class="muted" style="font-size:0.8rem;margin-top:8px">📍 <?php echo htmlspecialchars($od['address']); ?></div>
                <?php endif; ?>
              </td>
              <td><span class="badge badge-neutral"><?php echo htmlspecialchars($od['payment_method'] ?? 'COD'); ?></span></td>
              <td><strong style="color:#dc2626"><?php echo number_format($od['total']); ?> VND</strong></td>
              <td><?php echo status_badge($od['status'] ?? 'Chờ xử lý'); ?></td>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <a class="btn btn-secondary btn-sm" href="order_view.php?id=<?php echo (int)$od['id']; ?>">Xem chi tiết</a>
                  <?php if (($od['status'] ?? '') === 'Chờ xử lý'): ?>
                    <a class="btn btn-danger btn-sm" href="profile.php?cancel_order=<?php echo (int)$od['id']; ?>">Hủy đơn</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php layout_foot(); ?>