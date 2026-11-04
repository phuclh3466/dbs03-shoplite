<?php
require 'db.php';
require_login();

// ===== Kiem tra dinh dang thong tin giao hang =====
// Chuan hoa so dien thoai VN: bo khoang trang . - ( ), doi +84 / 84 thanh 0
function normalize_vn_phone($raw) {
    $p = preg_replace('/[\s.\-()]/', '', (string)$raw);
    if (preg_match('/^(\+84|84)/', $p)) {
        $p = '0' . preg_replace('/^(\+84|84)/', '', $p);
    }
    return $p;
}
// Tra ve chuoi loi (rong = hop le)
function phone_problem($raw) {
    $p = normalize_vn_phone($raw);
    if ($p === '')                           return 'Vui lòng nhập số điện thoại liên hệ.';
    if (!preg_match('/^[0-9]+$/', $p))       return 'Số điện thoại chỉ được chứa chữ số (có thể bắt đầu bằng +84).';
    if (strlen($p) !== 10)                   return 'Số điện thoại phải gồm đúng 10 chữ số, ví dụ: 0987654321.';
    if (!preg_match('/^0[35789]/', $p))      return 'Số điện thoại phải bắt đầu bằng 03, 05, 07, 08 hoặc 09.';
    return '';
}
function address_problem($a) {
    if ($a === '') return 'Vui lòng nhập địa chỉ giao hàng.';
    $len = preg_match_all('/./us', $a);
    if ($len === false) return 'Địa chỉ chứa ký tự không hợp lệ.';
    if ($len < 10)  return 'Địa chỉ quá ngắn, vui lòng ghi chi tiết (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành).';
    if ($len > 200) return 'Địa chỉ quá dài (tối đa 200 ký tự).';
    if (!preg_match('/^[\p{L}\p{N}\s,.\-\/()#]+$/u', $a)) {
        return 'Địa chỉ chỉ được chứa chữ, số và các ký tự , . - / ( ) #';
    }
    $parts = array_values(array_filter(array_map('trim', explode(',', $a)), function ($x) { return $x !== ''; }));
    if (count($parts) < 3) {
        return 'Địa chỉ cần ghi đủ ít nhất 3 phần, cách nhau bằng dấu phẩy: số nhà/đường, phường/xã hoặc quận/huyện, tỉnh/thành phố. Ví dụ: Số 12 Duy Tân, Cầu Giấy, Hà Nội.';
    }
    foreach ($parts as $part) {
        if (!preg_match('/[\p{L}\p{N}]/u', $part)) return 'Mỗi phần của địa chỉ phải có chữ hoặc số.';
    }
    return '';
}

if (empty($_SESSION['cart']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$c = db();

// Lay thong tin mac dinh cua user de dien san form giao hang
$uStmt = $c->prepare("SELECT username, full_name, phone, address FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['uid']]);
$userInfo = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$items = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $c->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $qty = (int)$_SESSION['cart'][$p['id']];
        $subtotal += $qty * (int)$p['price'];
        $items[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'qty' => $qty, 'price' => (int)$p['price']];
    }
}

$discount = 0;
$couponCode = '';
if ($subtotal > 0 && !empty($_SESSION['coupon'])) {
    $stmt = $c->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? AND active = 1");
    $stmt->execute([strtoupper($_SESSION['coupon'])]);
    $cp = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cp && $subtotal >= (int)$cp['min_order']) {
        $couponCode = $cp['code'];
        $discount = (int)round($subtotal * ((int)$cp['discount_percent'] / 100));
        if ($cp['max_discount'] > 0 && $discount > (int)$cp['max_discount']) {
            $discount = (int)$cp['max_discount'];
        }
    }
}
$shipping = ($subtotal > 0 && $subtotal < 300000) ? 25000 : 0;
$total = max(0, $subtotal - $discount + $shipping);

$orderId = null;
$errors = [];

// Gia tri hien tren form (giu lai khi nhap sai)
$form = [
    'recipient_name' => $userInfo['full_name'] ?: ($userInfo['username'] ?? ''),
    'phone'          => $userInfo['phone'] ?? '',
    'address'        => $userInfo['address'] ?? '',
    'note'           => '',
    'payment_method' => 'COD',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $items) {
    $recipient = trim($_POST['recipient_name'] ?? $form['recipient_name']);
    $phone     = trim($_POST['phone'] ?? $form['phone']);
    $address   = trim($_POST['address'] ?? $form['address']);
    $note      = trim($_POST['note'] ?? '');
    $payment   = $_POST['payment_method'] ?? 'COD';
    $form = ['recipient_name' => $recipient, 'phone' => $phone, 'address' => $address, 'note' => $note, 'payment_method' => $payment];

    if ($e = phone_problem($phone))     $errors[] = $e;
    if ($e = address_problem($address)) $errors[] = $e;

    if (!$errors) {
        $phone = normalize_vn_phone($phone);   // luu dang chuan 0xxxxxxxxx

        $stmt = $c->prepare("INSERT INTO orders (user_id, recipient_name, phone, address, note, payment_method, coupon_code, discount_amount, subtotal, total, status, items) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xử lý', ?)");
        $stmt->execute([
            $_SESSION['uid'],
            $recipient !== '' ? $recipient : $_SESSION['username'],
            $phone,
            $address,
            $note,
            $payment,
            $couponCode,
            $discount,
            $subtotal,
            $total,
            json_encode($items, JSON_UNESCAPED_UNICODE)
        ]);
        $orderId = $c->lastInsertId();

        // Tru ton kho
        $upStock = $c->prepare("UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?");
        foreach ($items as $it) {
            $upStock->execute([$it['qty'], $it['id']]);
        }

        unset($_SESSION['cart'], $_SESSION['coupon']);
    }
}

layout_head('ShopLite - Thanh toán');
?>
<h1>Thanh toán đơn hàng</h1>

<?php if ($orderId): ?>
  <div class="card" style="text-align:center;padding:36px 24px">
    <div style="font-size:48px;margin-bottom:8px">🎉</div>
    <p class="ok" style="display:inline-block;padding:10px 20px">Đặt hàng thành công! Mã đơn hàng: <strong>#<?php echo (int)$orderId; ?></strong></p>
    <p class="muted" style="max-width:480px;margin:10px auto 20px">Cảm ơn bạn đã mua sắm tại ShopLite. Bạn có thể theo dõi trạng thái xử lý đơn hàng trong mục Hồ sơ cá nhân.</p>
    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap">
      <a class="btn" href="profile.php">Xem đơn hàng của tôi</a>
      <a class="btn btn-secondary" href="index.php">Về trang chủ</a>
    </div>
  </div>
<?php else: ?>
  <div class="grid-2" style="grid-template-columns: 1.4fr 1fr; align-items:start">
    <div class="card" style="margin-top:0">
      <h3>Thông tin nhận hàng</h3>
      <?php if ($errors): ?>
        <div class="err"><strong>Chưa thể đặt hàng, vui lòng kiểm tra lại:</strong>
          <ul style="margin:6px 0 0 18px;padding:0">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <form method="post" id="checkoutForm" novalidate>
        <label>Họ và tên người nhận</label>
        <input name="recipient_name" value="<?php echo htmlspecialchars($form['recipient_name']); ?>" placeholder="Nhập họ tên người nhận" autocomplete="name" required>

        <label>Số điện thoại liên hệ</label>
        <input name="phone" id="coPhone" type="tel" inputmode="tel" maxlength="20" autocomplete="tel" value="<?php echo htmlspecialchars($form['phone']); ?>" placeholder="Ví dụ: 0987654321" required>
        <div class="field-hint" id="coPhoneMsg" data-default="10 chữ số, bắt đầu bằng 03/05/07/08/09 (có thể nhập +84)."></div>

        <label>Địa chỉ giao hàng chi tiết</label>
        <input name="address" id="coAddress" maxlength="200" autocomplete="street-address" value="<?php echo htmlspecialchars($form['address']); ?>" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành..." required>
        <div class="field-hint" id="coAddressMsg" data-default="Ghi đủ 3 phần trở lên, cách nhau bằng dấu phẩy. Ví dụ: Số 12 Duy Tân, Cầu Giấy, Hà Nội."></div>

        <label>Phương thức thanh toán</label>
        <select name="payment_method">
          <option value="COD" <?php echo $form['payment_method'] === 'COD' ? 'selected' : ''; ?>>💵 Thanh toán khi nhận hàng (COD)</option>
          <option value="BANK" <?php echo $form['payment_method'] === 'BANK' ? 'selected' : ''; ?>>🏦 Chuyển khoản ngân hàng (VietQR 24/7)</option>
          <option value="WALLET" <?php echo $form['payment_method'] === 'WALLET' ? 'selected' : ''; ?>>📱 Ví điện tử (MoMo / ZaloPay)</option>
        </select>

        <label>Ghi chú đơn hàng (Tùy chọn)</label>
        <textarea name="note" rows="2" placeholder="Ghi chú thời gian giao hàng hoặc hướng dẫn chỉ đường..."><?php echo htmlspecialchars($form['note']); ?></textarea>

        <button type="submit" class="btn btn-block" style="margin-top:10px">✅ Xác nhận đặt hàng (<?php echo number_format($total); ?> VND)</button>
      </form>
    </div>

    <div class="card" style="margin-top:0">
      <h3>Chi tiết sản phẩm (<?php echo count($items); ?>)</h3>
      <?php foreach ($items as $it): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:0.92rem">
          <span><?php echo htmlspecialchars($it['name']); ?> <strong>×<?php echo (int)$it['qty']; ?></strong></span>
          <strong><?php echo number_format($it['qty'] * $it['price']); ?>đ</strong>
        </div>
      <?php endforeach; ?>

      <div style="margin-top:14px;font-size:0.94rem">
        <div style="display:flex;justify-content:space-between;margin:6px 0">
          <span class="muted">Tạm tính:</span>
          <span><?php echo number_format($subtotal); ?> VND</span>
        </div>
        <?php if ($discount > 0): ?>
          <div style="display:flex;justify-content:space-between;margin:6px 0;color:var(--success)">
            <span>Mã giảm giá (<?php echo htmlspecialchars($couponCode); ?>):</span>
            <span>-<?php echo number_format($discount); ?> VND</span>
          </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;margin:6px 0">
          <span class="muted">Phí vận chuyển:</span>
          <span><?php echo $shipping === 0 ? 'Miễn phí' : number_format($shipping) . ' VND'; ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:1.15rem">
          <strong>Tổng thanh toán:</strong>
          <strong style="color:#dc2626"><?php echo number_format($total); ?> VND</strong>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php if (!$orderId): ?>
<style>
  .field-hint { font-size: 0.8rem; color: var(--text-muted); margin: -4px 0 10px; min-height: 1.1em; }
  .field-hint.bad { color: #dc2626; font-weight: 600; }
  .field-hint.good { color: #15803d; font-weight: 600; }
  #checkoutForm input.is-bad { border-color: #f87171 !important; box-shadow: 0 0 0 3px rgba(248,113,113,.18) !important; }
  #checkoutForm input.is-good { border-color: #4ade80 !important; }
</style>
<script>
(function () {
  const form = document.getElementById('checkoutForm');
  if (!form) return;
  const phone = document.getElementById('coPhone'), phoneMsg = document.getElementById('coPhoneMsg');
  const addr = document.getElementById('coAddress'), addrMsg = document.getElementById('coAddressMsg');

  // Cung quy tac voi kiem tra phia server (PHP)
  function normPhone(v) {
    let p = v.replace(/[\s.\-()]/g, '');
    if (/^(\+84|84)/.test(p)) p = '0' + p.replace(/^(\+84|84)/, '');
    return p;
  }
  function phoneProblem(v) {
    const p = normPhone(v);
    if (p === '') return 'Vui lòng nhập số điện thoại liên hệ.';
    if (!/^[0-9]+$/.test(p)) return 'Số điện thoại chỉ được chứa chữ số (có thể bắt đầu bằng +84).';
    if (p.length !== 10) return 'Số điện thoại phải gồm đúng 10 chữ số, ví dụ: 0987654321.';
    if (!/^0[35789]/.test(p)) return 'Số điện thoại phải bắt đầu bằng 03, 05, 07, 08 hoặc 09.';
    return '';
  }
  function addressProblem(v) {
    const a = v.trim();
    if (a === '') return 'Vui lòng nhập địa chỉ giao hàng.';
    const len = Array.from(a).length;
    if (len < 10) return 'Địa chỉ quá ngắn, vui lòng ghi chi tiết (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành).';
    if (len > 200) return 'Địa chỉ quá dài (tối đa 200 ký tự).';
    if (!/^[\p{L}\p{N}\s,.\-\/()#]+$/u.test(a)) return 'Địa chỉ chỉ được chứa chữ, số và các ký tự , . - / ( ) #';
    const parts = a.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
    if (parts.length < 3) return 'Địa chỉ cần ghi đủ ít nhất 3 phần, cách nhau bằng dấu phẩy: số nhà/đường, phường/xã hoặc quận/huyện, tỉnh/thành phố.';
    for (const part of parts) { if (!/[\p{L}\p{N}]/u.test(part)) return 'Mỗi phần của địa chỉ phải có chữ hoặc số.'; }
    return '';
  }

  function bind(input, msgEl, check) {
    let touched = input.value !== '';
    function run(show) {
      const err = check(input.value);
      input.setCustomValidity(err);
      if (show) {
        input.classList.toggle('is-bad', !!err);
        input.classList.toggle('is-good', !err);
        msgEl.className = 'field-hint ' + (err ? 'bad' : 'good');
        msgEl.textContent = err ? err : 'Định dạng hợp lệ.';
      } else {
        input.classList.remove('is-bad', 'is-good');
        msgEl.className = 'field-hint';
        msgEl.textContent = msgEl.dataset.default;
      }
    }
    input.addEventListener('input', function () { touched = true; run(true); });
    input.addEventListener('blur', function () { if (touched) run(true); });
    run(touched && input.value !== '');
    return function () { touched = true; run(true); };
  }
  const showPhone = bind(phone, phoneMsg, phoneProblem);
  const showAddr  = bind(addr, addrMsg, addressProblem);

  form.addEventListener('submit', function (e) {
    showPhone(); showAddr();
    if (!form.checkValidity()) {
      e.preventDefault();
      form.reportValidity();
    }
  });
})();
</script>
<?php endif; ?>
<?php if ($orderId): ?>
<script>
(function () {
  // Two gentle confirmation tones; browsers may defer sound until the first tap/click.
  let played = false;
  function playOrderChime() {
    if (played) return; played = true;
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const tones = [{f:880,t:0},{f:1174.66,t:.16}];
      tones.forEach(({f,t}) => {
        const osc = ctx.createOscillator(), gain = ctx.createGain();
        osc.type = 'sine'; osc.frequency.value = f;
        gain.gain.setValueAtTime(.0001, ctx.currentTime + t);
        gain.gain.exponentialRampToValueAtTime(.12, ctx.currentTime + t + .025);
        gain.gain.exponentialRampToValueAtTime(.0001, ctx.currentTime + t + .22);
        osc.connect(gain); gain.connect(ctx.destination);
        osc.start(ctx.currentTime + t); osc.stop(ctx.currentTime + t + .24);
      });
      window.setTimeout(() => ctx.close().catch(() => {}), 700);
    } catch (e) { /* Audio is optional; order confirmation must still work. */ }
  }
  window.addEventListener('load', () => {
    playOrderChime();
    if (!played) {
      const retry = () => { playOrderChime(); document.removeEventListener('pointerdown', retry); document.removeEventListener('keydown', retry); };
      document.addEventListener('pointerdown', retry, {once:true}); document.addEventListener('keydown', retry, {once:true});
    }
  });
})();
</script>
<?php endif; ?>
<?php layout_foot(); ?>