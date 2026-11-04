<?php
require 'db.php';

$c = db();
$msg = '';

// Xoa 1 san pham khoi gio
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header('Location: cart.php');
    exit;
}

// Xoa toan bo gio hang
if (isset($_GET['clear'])) {
    unset($_SESSION['cart'], $_SESSION['coupon']);
    header('Location: cart.php');
    exit;
}

// Cap nhat so luong hoac ap ma giam gia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_qty']) && is_array($_POST['qty'] ?? null)) {
        foreach ($_POST['qty'] as $pid => $q) {
            $pid = (int)$pid;
            $q = (int)$q;
            if ($q <= 0) {
                unset($_SESSION['cart'][$pid]);
            } else {
                $_SESSION['cart'][$pid] = min(99, $q);
            }
        }
        $msg = '<div class="ok">Đã cập nhật số lượng giỏ hàng.</div>';
    }

    if (isset($_POST['apply_coupon'])) {
        $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
        if ($code === '') {
            unset($_SESSION['coupon']);
            $msg = '<div class="ok">Đã gỡ mã giảm giá.</div>';
        } else {
            $stmt = $c->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? AND active = 1");
            $stmt->execute([$code]);
            $cp = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cp) {
                $_SESSION['coupon'] = $cp['code'];
                $msg = '<div class="ok">Đã áp dụng mã giảm giá <strong>' . htmlspecialchars($cp['code']) . '</strong>!</div>';
            } else {
                $msg = '<div class="err">Mã giảm giá không tồn tại hoặc đã hết hạn.</div>';
            }
        }
    }
}

$items = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $c->prepare("SELECT id, name, category, price, stock, icon, image_url FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $qty = (int)$_SESSION['cart'][$p['id']];
        $lineTotal = $qty * (int)$p['price'];
        $subtotal += $lineTotal;
        $items[] = $p + ['qty' => $qty, 'subtotal' => $lineTotal];
    }
}

// Tinh giam gia voucher & phi ship
$discount = 0;
$appliedCoupon = null;
if ($subtotal > 0 && !empty($_SESSION['coupon'])) {
    $stmt = $c->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? AND active = 1");
    $stmt->execute([strtoupper($_SESSION['coupon'])]);
    $cp = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cp && $subtotal >= (int)$cp['min_order']) {
        $appliedCoupon = $cp;
        $discount = (int)round($subtotal * ((int)$cp['discount_percent'] / 100));
        if ($cp['max_discount'] > 0 && $discount > (int)$cp['max_discount']) {
            $discount = (int)$cp['max_discount'];
        }
    } elseif ($cp) {
        $msg = '<div class="err">Đơn hàng cần tối thiểu ' . number_format($cp['min_order']) . ' VND để dùng mã ' . htmlspecialchars($cp['code']) . '.</div>';
        unset($_SESSION['coupon']);
    }
}

$shipping = ($subtotal > 0 && $subtotal < 300000) ? 25000 : 0;
$total = max(0, $subtotal - $discount + $shipping);

layout_head('ShopLite - Giỏ hàng');
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:8px">
  <h1 style="margin:0">Giỏ hàng của bạn</h1>
  <?php if ($items): ?>
    <a class="btn btn-danger btn-sm" href="cart.php?clear=1">🗑️ Xóa toàn bộ giỏ</a>
  <?php endif; ?>
</div>

<?php echo $msg; ?>

<?php if (!$items): ?>
  <div class="card" style="text-align:center;padding:48px 20px">
    <div style="font-size:44px;margin-bottom:10px">🛒</div>
    <h3>Giỏ hàng của bạn đang trống</h3>
    <p class="muted">Hãy khám phá các sản phẩm nổi bật tại cửa hàng và thêm vào giỏ nhé.</p>
    <a class="btn" href="index.php" style="margin-top:10px">Tiếp tục mua sắm</a>
  </div>
<?php else: ?>
  <div class="grid-2" style="grid-template-columns: 1.65fr 1fr; align-items:start">
    <div class="card" style="margin-top:0">
      <form method="post">
        <input type="hidden" name="update_qty" value="1">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Đơn giá</th>
                <th style="width:100px">Số lượng</th>
                <th>Thành tiền</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:12px">
                      <div style="width:52px;height:48px;border-radius:8px;overflow:hidden;border:1px solid var(--border);flex-shrink:0">
                        <?php echo !empty($it['image_url'])
                          ? '<img src="' . htmlspecialchars($it['image_url']) . '" style="width:100%;height:100%;object-fit:cover">'
                          : product_svg($it['icon'] ?? 'shirt'); ?>
                      </div>
                      <div>
                        <a href="index.php?view=<?php echo (int)$it['id']; ?>" style="font-weight:700;color:var(--text);text-decoration:none">
                          <?php echo htmlspecialchars($it['name']); ?>
                        </a>
                        <div class="muted" style="font-size:0.8rem"><?php echo htmlspecialchars($it['category'] ?? ''); ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo number_format($it['price']); ?>đ</td>
                  <td>
                    <input type="number" name="qty[<?php echo (int)$it['id']; ?>]" value="<?php echo (int)$it['qty']; ?>" min="0" max="99" style="margin:0;padding:7px 10px">
                  </td>
                  <td><strong><?php echo number_format($it['subtotal']); ?> VND</strong></td>
                  <td><a class="btn btn-danger btn-sm" href="cart.php?remove=<?php echo (int)$it['id']; ?>">Xóa</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:10px">
          <a class="btn btn-secondary" href="index.php">← Mua thêm sản phẩm</a>
          <button type="submit" class="btn btn-secondary">🔄 Cập nhật số lượng</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:0">
      <h3>Mã ưu đãi & Tóm tắt đơn hàng</h3>
      <form method="post" style="display:flex;gap:8px;margin-bottom:16px">
        <input type="hidden" name="apply_coupon" value="1">
        <input type="text" name="coupon_code" value="<?php echo htmlspecialchars($_SESSION['coupon'] ?? ''); ?>" placeholder="Nhập mã (VD: FCTF10)" style="margin:0">
        <button type="submit" class="btn btn-sm">Áp dụng</button>
      </form>

      <div style="border-top:1px solid var(--border);padding-top:14px;font-size:0.95rem">
        <div style="display:flex;justify-content:space-between;margin:8px 0">
          <span class="muted">Tạm tính:</span>
          <strong><?php echo number_format($subtotal); ?> VND</strong>
        </div>
        <?php if ($discount > 0): ?>
          <div style="display:flex;justify-content:space-between;margin:8px 0;color:var(--success)">
            <span>Giảm giá (<?php echo htmlspecialchars($appliedCoupon['code']); ?>):</span>
            <strong>-<?php echo number_format($discount); ?> VND</strong>
          </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;margin:8px 0">
          <span class="muted">Phí vận chuyển:</span>
          <span><?php echo $shipping === 0 ? '<strong style="color:var(--success)">Miễn phí</strong>' : number_format($shipping) . ' VND'; ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin:14px 0 18px;padding-top:12px;border-top:1px solid var(--border);font-size:1.15rem">
          <strong>Tổng cộng:</strong>
          <strong style="color:#dc2626"><?php echo number_format($total); ?> VND</strong>
        </div>
        <a class="btn btn-block" href="checkout.php">Tiến hành thanh toán →</a>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>