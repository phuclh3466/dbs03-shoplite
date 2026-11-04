<?php
require '../db.php';
require_admin();

$c = db();
$msg = '';

// Xu ly cac thao tac quan tri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Them san pham moi
    if ($action === 'add_product') {
        $name  = trim($_POST['name'] ?? '');
        $cat   = trim($_POST['category'] ?? 'Phụ kiện');
        $price = max(0, (int)($_POST['price'] ?? 0));
        $oldP  = max(0, (int)($_POST['old_price'] ?? 0));
        $stock = max(0, (int)($_POST['stock'] ?? 20));
        $badge = trim($_POST['badge'] ?? '');
        $icon  = trim($_POST['icon'] ?? 'shirt');
        $imgUrl = trim($_POST['image_url'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $specs = trim($_POST['specs'] ?? '');

        if ($name !== '' && $price > 0) {
            $stmt = $c->prepare("INSERT INTO products (name, category, price, old_price, stock, badge, icon, image_url, description, specs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $cat, $price, $oldP, $stock, $badge, $icon, $imgUrl, $desc, $specs]);
            $msg = '<div class="ok">Đã thêm sản phẩm mới: <strong>' . htmlspecialchars($name) . '</strong>.</div>';
        } else {
            $msg = '<div class="err">Vui lòng nhập tên sản phẩm và giá hợp lệ.</div>';
        }
    }

    // 2. Xoa san pham
    if ($action === 'delete_product') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $stmt = $c->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $msg = '<div class="ok">Đã xóa sản phẩm #' . $pid . '.</div>';
    }

    // 3. Cap nhat trang thai don hang
    if ($action === 'update_order_status') {
        $oid    = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? 'Chờ xử lý';
        $stmt = $c->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $oid]);
        $msg = '<div class="ok">Đã cập nhật trạng thái đơn hàng #' . $oid . ' thành <strong>' . htmlspecialchars($status) . '</strong>.</div>';
    }

    // 4. Them ma giam gia
    if ($action === 'add_coupon') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $pct  = max(1, min(90, (int)($_POST['discount_percent'] ?? 10)));
        $maxD = max(0, (int)($_POST['max_discount'] ?? 50000));
        $minO = max(0, (int)($_POST['min_order'] ?? 0));
        $desc = trim($_POST['description'] ?? '');
        if ($code !== '') {
            $stmt = $c->prepare("INSERT OR REPLACE INTO coupons (code, discount_percent, max_discount, min_order, description, active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$code, $pct, $maxD, $minO, $desc]);
            $msg = '<div class="ok">Đã lưu mã giảm giá <strong>' . htmlspecialchars($code) . '</strong>.</div>';
        }
    }
}

// ===== DA SUA (DBS-03 chain fix) =====
// TRUOC: trang nay doc truc tiep flag.txt va hien thi cho moi admin, khien
// flag kho nhat (upload -> RCE) co the lay duoc chi bang cach dang nhap admin
// (vd. qua SQLi o api_search.php dump duoc bang users). Da go bo de dam bao
// flag #5 CHI lay duoc qua dung chuoi khai thac upload -> webshell -> doc file.

// Thong ke KPI
$totalRevenue = (int)$c->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'Đã hủy'")->fetchColumn();
$totalOrders  = (int)$c->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalProds   = (int)$c->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers   = (int)$c->query("SELECT COUNT(*) FROM users")->fetchColumn();

$orders   = $c->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products = $c->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$users    = $c->query("SELECT id, username, role, full_name, email, phone, avatar, created_at FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$coupons  = $c->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

layout_head('ShopLite - Quản trị hệ thống');
?>
<h1>⚡ Bảng điều khiển Quản trị viên</h1>

<?php echo $msg; ?>

<!-- Thong ke KPI -->
<div class="grid-4" style="margin:18px 0">
  <div class="stat-card">
    <div class="stat-label">Doanh thu ghi nhận</div>
    <div class="stat-value" style="color:var(--primary)"><?php echo number_format($totalRevenue); ?>đ</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Tổng đơn hàng</div>
    <div class="stat-value"><?php echo $totalOrders; ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Sản phẩm đang bán</div>
    <div class="stat-value"><?php echo $totalProds; ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Tài khoản thành viên</div>
    <div class="stat-value"><?php echo $totalUsers; ?></div>
  </div>
</div>

<!-- Quan ly Don hang -->
<div class="card">
  <h2>📦 Quản lý Đơn hàng (<?php echo count($orders); ?>)</h2>
  <?php if (!$orders): ?>
    <p class="muted">Chưa có đơn hàng nào trên hệ thống.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Mã</th>
            <th>Khách hàng</th>
            <th>Chi tiết</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Cập nhật</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $od): ?>
            <tr>
              <td><strong>#<?php echo (int)$od['id']; ?></strong></td>
              <td>
                <strong><?php echo htmlspecialchars($od['recipient_name'] ?: ($od['username'] ?? 'Khách')); ?></strong>
                <div class="muted" style="font-size:0.8rem"><?php echo htmlspecialchars($od['phone'] ?? ''); ?></div>
              </td>
              <td style="font-size:0.85rem">
                <?php echo htmlspecialchars($od['items']); ?>
                <?php if (!empty($od['coupon_code'])): ?>
                  <div><span class="badge badge-info">Mã: <?php echo htmlspecialchars($od['coupon_code']); ?></span></div>
                <?php endif; ?>
              </td>
              <td><strong style="color:#dc2626"><?php echo number_format($od['total']); ?>đ</strong></td>
              <td><?php echo status_badge($od['status'] ?? 'Chờ xử lý'); ?></td>
              <td>
                <form method="post" style="display:flex;gap:6px;align-items:center">
                  <input type="hidden" name="action" value="update_order_status">
                  <input type="hidden" name="order_id" value="<?php echo (int)$od['id']; ?>">
                  <select name="status" style="margin:0;padding:6px 8px;width:130px;font-size:0.84rem">
                    <?php foreach (['Chờ xử lý', 'Đang giao', 'Hoàn thành', 'Đã hủy'] as $st): ?>
                      <option value="<?php echo $st; ?>" <?php echo (($od['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-sm">Lưu</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="grid-2" style="align-items:start">
  <!-- Them san pham moi -->
  <div class="card">
    <h2>➕ Thêm sản phẩm mới</h2>
    <form method="post">
      <input type="hidden" name="action" value="add_product">
      <label>Tên sản phẩm *</label>
      <input name="name" placeholder="Ví dụ: Áo khoác gió F-CTF" required>

      <div class="grid-2" style="gap:10px">
        <div>
          <label>Danh mục</label>
          <select name="category">
            <option value="Thời trang">Thời trang</option>
            <option value="Phụ kiện">Phụ kiện</option>
            <option value="Thiết bị số">Thiết bị số</option>
            <option value="Sách & Lab">Sách & Lab</option>
          </select>
        </div>
        <div>
          <label>Hình minh họa có sẵn</label>
          <select name="icon">
            <option value="shirt">Áo thun (shirt)</option>
            <option value="polo">Áo Polo (polo)</option>
            <option value="hoodie">Áo hoodie (hoodie)</option>
            <option value="bomber">Áo Bomber (bomber)</option>
            <option value="cap">Mũ lưỡi trai (cap)</option>
            <option value="bag">Balo (bag)</option>
            <option value="sling">Túi đeo chéo (sling)</option>
            <option value="keyboard">Bàn phím (keyboard)</option>
            <option value="mouse">Chuột (mouse)</option>
            <option value="headset">Tai nghe (headset)</option>
            <option value="yubikey">Khóa FIDO2 (yubikey)</option>
            <option value="usb">USB OTG (usb)</option>
            <option value="hub">Hub Type-C (hub)</option>
            <option value="stand">Giá đỡ Laptop (stand)</option>
            <option value="bottle">Bình nước (bottle)</option>
            <option value="mug">Cốc sứ (mug)</option>
            <option value="book">Sách (book)</option>
            <option value="notebook">Sổ tay (notebook)</option>
            <option value="sticker">Sticker (sticker)</option>
            <option value="webcam">Webcam (webcam)</option>
            <option value="powerbank">Sạc dự phòng (powerbank)</option>
            <option value="charger">Củ sạc (charger)</option>
            <option value="cable">Cáp sạc (cable)</option>
            <option value="speaker">Loa Bluetooth (speaker)</option>
            <option value="earbuds">Tai nghe True Wireless (earbuds)</option>
            <option value="lamp">Đèn bàn (lamp)</option>
            <option value="board">Bo mạch Raspberry Pi (board)</option>
            <option value="monitor">Màn hình (monitor)</option>
            <option value="lanyard">Dây đeo thẻ (lanyard)</option>
            <option value="socks">Tất (socks)</option>
            <option value="pybook">Sách Python (pybook)</option>
            <option value="mousepad">Lót chuột (mousepad)</option>
          </select>
        </div>
      </div>

      <label>Hoặc Link ảnh sản phẩm (URL tùy chọn)</label>
      <input name="image_url" placeholder="https://example.com/product.jpg (để trống sẽ dùng hình minh họa màu)">

      <div class="grid-3" style="gap:10px">
        <div>
          <label>Giá bán (VND) *</label>
          <input type="number" name="price" placeholder="190000" required>
        </div>
        <div>
          <label>Giá gốc (VND)</label>
          <input type="number" name="old_price" placeholder="250000">
        </div>
        <div>
          <label>Tồn kho</label>
          <input type="number" name="stock" value="30">
        </div>
      </div>

      <label>Nhãn nổi bật (Badge)</label>
      <input name="badge" placeholder="Mới / Hot / Bán chạy">

      <label>Mô tả ngắn</label>
      <textarea name="description" rows="2" placeholder="Mô tả đặc điểm nổi bật của sản phẩm..."></textarea>

      <label>Thông số kỹ thuật</label>
      <input name="specs" placeholder="Chất liệu: ... | Kích thước: ...">

      <button type="submit" style="margin-top:8px">Thêm sản phẩm</button>
    </form>
  </div>

  <!-- Quan ly Voucher -->
  <div class="card">
    <h2>🎟️ Quản lý Mã giảm giá (Voucher)</h2>
    <form method="post" style="margin-bottom:16px">
      <input type="hidden" name="action" value="add_coupon">
      <div class="grid-2" style="gap:10px">
        <div>
          <label>Mã Code *</label>
          <input name="code" placeholder="VD: SALE30" required>
        </div>
        <div>
          <label>% Giảm *</label>
          <input type="number" name="discount_percent" value="15" min="1" max="90" required>
        </div>
      </div>
      <div class="grid-2" style="gap:10px">
        <div>
          <label>Giảm tối đa (VND)</label>
          <input type="number" name="max_discount" value="50000">
        </div>
        <div>
          <label>Đơn tối thiểu (VND)</label>
          <input type="number" name="min_order" value="150000">
        </div>
      </div>
      <label>Mô tả ưu đãi</label>
      <input name="description" placeholder="Giảm 15% tối đa 50.000đ cho đơn từ 150.000đ">
      <button type="submit" class="btn btn-sm">Lưu mã giảm giá</button>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Mã</th><th>Mức giảm</th><th>Đơn tối thiểu</th></tr>
        </thead>
        <tbody>
          <?php foreach ($coupons as $cp): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($cp['code']); ?></strong></td>
              <td><?php echo (int)$cp['discount_percent']; ?>% (Tối đa <?php echo number_format($cp['max_discount']); ?>đ)</td>
              <td><?php echo number_format($cp['min_order']); ?>đ</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Danh sach san pham -->
<div class="card">
  <h2>🛍️ Danh mục Sản phẩm hiện có (<?php echo count($products); ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Hình</th>
          <th>Tên sản phẩm</th>
          <th>Danh mục</th>
          <th>Giá bán</th>
          <th>Tồn kho</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>#<?php echo (int)$p['id']; ?></td>
            <td>
              <div style="width:52px;height:48px;border-radius:8px;overflow:hidden;border:1px solid var(--border)">
                <?php echo !empty($p['image_url'])
                  ? '<img src="' . htmlspecialchars($p['image_url']) . '" style="width:100%;height:100%;object-fit:cover">'
                  : product_svg($p['icon'] ?? 'shirt'); ?>
              </div>
            </td>
            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($p['category'] ?? ''); ?></td>
            <td><?php echo number_format($p['price']); ?> VND</td>
            <td><?php echo (int)($p['stock'] ?? 0); ?></td>
            <td>
              <form method="post" style="margin:0">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Danh sach nguoi dung -->
<div class="card">
  <h2>👥 Danh sách Tài khoản (<?php echo count($users); ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Họ tên</th>
          <th>Vai trò</th>
          <th>Avatar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>#<?php echo (int)$u['id']; ?></td>
            <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
            <td><?php echo htmlspecialchars($u['full_name'] ?: '—'); ?></td>
            <td><span class="badge <?php echo $u['role'] === 'admin' ? 'badge-info' : 'badge-neutral'; ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
            <td class="muted"><?php echo htmlspecialchars($u['avatar'] ?: 'Chưa cập nhật'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_foot(); ?>