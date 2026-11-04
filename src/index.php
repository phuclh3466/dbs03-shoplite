<?php
require 'db.php';

$c = db();
$msg = '';

// Helper giu nguyen bo loc (q, cat, sort, page) khi thao tac tren danh sach
function build_filter_query(array $extra = []) {
    $params = [];
    foreach (['q', 'cat', 'sort', 'page'] as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '' && !($k === 'sort' && $_GET[$k] === 'default')) {
            $params[$k] = $_GET[$k];
        }
    }
    foreach ($extra as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return http_build_query($params);
}

// Danh sach yeu thich luu theo session
if (isset($_GET['wish'])) {
    $wishId = (int)$_GET['wish'];
    if ($wishId > 0) {
        if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];
        if (isset($_SESSION['wishlist'][$wishId])) {
            unset($_SESSION['wishlist'][$wishId]);
            $extra = ['wish_removed' => 1];
        } else {
            $_SESSION['wishlist'][$wishId] = $wishId;
            $extra = ['wish_added' => 1];
        }
        if (!empty($_GET['view'])) {
            $extra['view'] = (int)$_GET['view'];
        }
        header('Location: index.php?' . build_filter_query($extra));
        exit;
    }
}

// Them san pham vao gio
if (isset($_GET['add'])) {
    $pid = (int)$_GET['add'];
    $qty = max(1, (int)($_GET['qty'] ?? 1));
    $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
    if (!empty($_GET['view'])) {
        header('Location: index.php?view=' . (int)$_GET['view'] . '&added=1');
    } else {
        header('Location: index.php?' . build_filter_query(['added' => 1]));
    }
    exit;
}

// Gui danh gia san pham: can dang nhap, da mua va moi tai khoan chi danh gia mot lan/san pham
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    require_login();
    $pid = (int)($_POST['product_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    $token = (string)($_POST['csrf_token'] ?? '');

    if (empty($_SESSION['csrf_review'])) {
        $_SESSION['csrf_review'] = bin2hex(random_bytes(32));
    }

    if (!hash_equals($_SESSION['csrf_review'], $token)) {
        $msg = '<div class="err">Phiên gửi đánh giá không hợp lệ. Vui lòng tải lại trang và thử lại.</div>';
    } elseif ($pid <= 0 || !$c->query("SELECT 1 FROM products WHERE id = " . $pid)->fetchColumn()) {
        $msg = '<div class="err">Sản phẩm không hợp lệ.</div>';
    } elseif ($rating < 1 || $rating > 5) {
        $msg = '<div class="err">Điểm đánh giá phải từ 1 đến 5 sao.</div>';
    } elseif ($comment === '' || (preg_match_all('/./us', $comment, $commentChars) !== false && count($commentChars[0]) > 1000)) {
        $msg = '<div class="err">Nhận xét không được để trống và tối đa 1.000 ký tự.</div>';
    } else {
        // Chi cho phep danh gia neu don hang cua nguoi dung da hoan thanh va co san pham nay.
        $ordersStmt = $c->prepare("SELECT items FROM orders WHERE user_id = ? AND status = 'Hoàn thành'");
        $ordersStmt->execute([$_SESSION['uid']]);
        $hasPurchased = false;
        foreach ($ordersStmt->fetchAll(PDO::FETCH_COLUMN) as $orderItemsJson) {
            $orderItems = json_decode($orderItemsJson, true);
            if (is_array($orderItems)) {
                foreach ($orderItems as $orderItem) {
                    if ((int)($orderItem['id'] ?? 0) === $pid) {
                        $hasPurchased = true;
                        break 2;
                    }
                }
            }
        }

        if (!$hasPurchased) {
            $msg = '<div class="err">Bạn chỉ có thể đánh giá sản phẩm trong đơn hàng đã hoàn thành.</div>';
        } else {
            $dup = $c->prepare("SELECT COUNT(*) FROM reviews WHERE product_id = ? AND user_id = ?");
            $dup->execute([$pid, $_SESSION['uid']]);
            if ((int)$dup->fetchColumn() > 0) {
                $msg = '<div class="err">Bạn đã đánh giá sản phẩm này rồi.</div>';
            } else {
                $stmt = $c->prepare("INSERT INTO reviews (product_id, user_id, username, rating, comment) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$pid, $_SESSION['uid'], $_SESSION['username'], $rating, $comment]);
                header('Location: index.php?view=' . $pid . '&reviewed=1');
                exit;
            }
        }
    }
}

layout_head('ShopLite - Mua sắm công nghệ & phụ kiện');

// 1. CHE DO XEM CHI TIET SAN PHAM (?view=ID)
if (!empty($_GET['view'])):
    $pid = (int)$_GET['view'];
    $stmt = $c->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product):
        echo '<div class="card"><p class="err">Sản phẩm không tồn tại.</p><a class="btn btn-secondary" href="index.php">← Quay lại danh sách</a></div>';
    else:
        $rStmt = $c->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY id DESC");
        $rStmt->execute([$pid]);
        $reviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);
        $avgRating = null;
        if ($reviews) {
            $sum = 0;
            foreach ($reviews as $rv) { $sum += (int)$rv['rating']; }
            $avgRating = round($sum / count($reviews), 1);
        }
?>
  <div style="margin-bottom:14px">
    <a class="btn btn-secondary btn-sm" href="index.php">← Quay lại danh sách sản phẩm</a>
  </div>

  <?php if (!empty($_GET['added'])): ?>
    <div class="ok">Đã thêm <strong><?php echo htmlspecialchars($product['name']); ?></strong> vào giỏ hàng! <a href="cart.php" style="font-weight:700;color:#15803d;margin-left:8px">Xem giỏ hàng →</a></div>
  <?php endif; ?>
  <?php if (!empty($_GET['reviewed'])): ?>
    <div class="ok">Cảm ơn bạn đã gửi đánh giá cho sản phẩm!</div>
  <?php endif; ?>
  <?php echo $msg; ?>

  <div class="card">
    <div class="grid-2" style="align-items:center;gap:28px">
      <div>
        <?php echo render_product_thumb($product, true); ?>
      </div>

      <div>
        <div class="product-cat"><?php echo htmlspecialchars($product['category'] ?? 'Sản phẩm'); ?></div>
        <h1 style="margin:4px 0 10px"><?php echo htmlspecialchars($product['name']); ?></h1>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
          <span style="color:#f59e0b;font-weight:700"><?php echo $avgRating === null ? 'Chưa có điểm' : '★ ' . $avgRating . '/5'; ?></span>
          <span class="muted">(<?php echo count($reviews); ?> đánh giá)</span>
          <span class="badge <?php echo ((int)($product['stock'] ?? 10) > 0) ? 'badge-ok' : 'badge-err'; ?>">
            <?php echo ((int)($product['stock'] ?? 10) > 0) ? 'Còn hàng (' . (int)($product['stock'] ?? 10) . ')' : 'Hết hàng'; ?>
          </span>
        </div>

        <div class="price-row" style="margin:14px 0">
          <span class="price-current" style="font-size:1.6rem"><?php echo number_format($product['price']); ?> VND</span>
          <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
            <span class="price-old" style="font-size:1rem"><?php echo number_format($product['old_price']); ?> VND</span>
          <?php endif; ?>
        </div>

        <p style="color:#334155;margin-bottom:14px"><?php echo htmlspecialchars($product['description']); ?></p>

        <?php if (!empty($product['specs'])): ?>
          <div style="background:var(--surface-alt);padding:12px 14px;border-radius:10px;margin-bottom:18px;font-size:0.88rem;color:#475569">
            <strong>Thông số kỹ thuật:</strong><br>
            <?php echo htmlspecialchars($product['specs']); ?>
          </div>
        <?php endif; ?>

        <form method="get" action="index.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <input type="hidden" name="add" value="<?php echo (int)$product['id']; ?>">
          <input type="hidden" name="view" value="<?php echo (int)$product['id']; ?>">
          <input type="number" name="qty" value="1" min="1" max="<?php echo max(1, (int)($product['stock'] ?? 50)); ?>" style="width:90px;margin:0">
          <button type="submit">🛒 Thêm vào giỏ hàng</button>
          <a class="btn btn-secondary" href="index.php?wish=<?php echo (int)$product['id']; ?>&view=<?php echo (int)$product['id']; ?>">♡ <?php echo isset($_SESSION['wishlist'][(int)$product['id']]) ? 'Bỏ yêu thích' : 'Yêu thích'; ?></a>
          <a class="btn btn-secondary" href="cart.php">Đến giỏ hàng</a>
        </form>
      </div>
    </div>
  </div>

  <div class="grid-2" style="align-items:start">
    <div class="card">
      <h3>Đánh giá từ khách hàng (<?php echo count($reviews); ?>)</h3>
      <?php if (!$reviews): ?>
        <p class="muted">Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên nhận xét!</p>
      <?php else: ?>
        <?php foreach ($reviews as $rv): ?>
          <div style="border-bottom:1px solid var(--border);padding:12px 0">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <strong><?php echo htmlspecialchars($rv['username']); ?></strong>
              <span style="color:#f59e0b;font-weight:700"><?php echo str_repeat('★', (int)$rv['rating']); ?></span>
            </div>
            <p style="margin:6px 0;color:#334155"><?php echo htmlspecialchars($rv['comment']); ?></p>
            <span class="muted" style="font-size:0.78rem"><?php echo htmlspecialchars($rv['created_at']); ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Viết đánh giá của bạn</h3>
      <?php if (empty($_SESSION['uid'])): ?>
        <p class="muted">Vui lòng <a href="login.php">đăng nhập</a> để gửi nhận xét và chấm điểm cho sản phẩm.</p>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="action" value="review">
          <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_review'] ??= bin2hex(random_bytes(32))); ?>">
          <label>Điểm đánh giá</label>
          <select name="rating">
            <option value="5">★★★★★ - Tuyệt vời (5 sao)</option>
            <option value="4">★★★★☆ - Hài lòng (4 sao)</option>
            <option value="3">★★★☆☆ - Bình thường (3 sao)</option>
            <option value="2">★★☆☆☆ - Tạm được (2 sao)</option>
            <option value="1">★☆☆☆☆ - Không hài lòng (1 sao)</option>
          </select>
          <label>Nội dung nhận xét</label>
          <textarea name="comment" rows="3" maxlength="1000" placeholder="Chia sẻ cảm nhận của bạn về chất lượng sản phẩm... (tối đa 1.000 ký tự)" required></textarea>
          <p class="muted" style="font-size:0.82rem">Chỉ tài khoản đã mua sản phẩm trong đơn hàng hoàn thành mới được đánh giá. Mỗi tài khoản được đánh giá một lần cho mỗi sản phẩm.</p>
          <button type="submit">Gửi đánh giá</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php
    endif;
else:
    // 2. TRANG CHU: BO LOC + DANH SACH SAN PHAM + PHAN TRANG (1, 2, 3...)
    $q       = trim($_GET['q'] ?? '');
    $cat     = trim($_GET['cat'] ?? '');
    $sort    = $_GET['sort'] ?? 'default';
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 8; // 8 san pham / trang (2 hang x 4 cot)

    $where  = [];
    $params = [];
    if ($q !== '') {
        $where[]  = "(name LIKE ? OR description LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($cat !== '') {
        $where[]  = "category = ?";
        $params[] = $cat;
    }

    $whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";

    // Lay danh sach san pham thoa bo loc va sap xep chuan xac (ho tro ca tieng Viet co dau A->Z, Z->A va ep kieu gia so)
    $stmt = $c->prepare("SELECT * FROM products" . $whereSql . " ORDER BY id ASC");
    $stmt->execute($params);
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($sort === 'price_asc') {
        usort($allProducts, function ($a, $b) {
            return ((int)$a['price'] <=> (int)$b['price']) ?: ((int)$a['id'] <=> (int)$b['id']);
        });
    } elseif ($sort === 'price_desc') {
        usort($allProducts, function ($a, $b) {
            return ((int)$b['price'] <=> (int)$a['price']) ?: ((int)$a['id'] <=> (int)$b['id']);
        });
    } elseif ($sort === 'name_asc') {
        usort($allProducts, function ($a, $b) {
            return strcmp(vn_sort_key($a['name']), vn_sort_key($b['name'])) ?: ((int)$a['id'] <=> (int)$b['id']);
        });
    } elseif ($sort === 'name_desc') {
        usort($allProducts, function ($a, $b) {
            return strcmp(vn_sort_key($b['name']), vn_sort_key($a['name'])) ?: ((int)$a['id'] <=> (int)$b['id']);
        });
    }

    $totalItems = count($allProducts);
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;
    $products = array_slice($allProducts, $offset, $perPage);

    $cats    = $c->query("SELECT DISTINCT category FROM products ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
    $coupons = $c->query("SELECT code, description FROM coupons WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC);

    // Helper tao URL giu nguyen bo loc khi chuyen trang
    $buildPageUrl = function($targetPage) use ($q, $cat, $sort) {
        $query = [];
        if ($q !== '')            $query['q']    = $q;
        if ($cat !== '')          $query['cat']  = $cat;
        if ($sort !== 'default')  $query['sort'] = $sort;
        $query['page'] = $targetPage;
        return 'index.php?' . http_build_query($query);
    };
?>
  <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <?php
      // Flag #1 (SQL Injection - Authentication Bypass): chi hien thi khi
      // session hien tai la role=admin. Mat khau admin that khong the doan
      // duoc va khong con lay duoc qua SQLi o api_search.php (da bi loc
      // bang 'users'), nen duong di dung y duy nhat de dang nhap admin la
      // khai thac loi bypass xac thuc o login.php.
      $flag1 = $c->query("SELECT secret FROM flag_level1 LIMIT 1")->fetchColumn();
    ?>
    <div class="card" style="border:2px dashed #16a34a;margin-bottom:14px">
      🔑 Chào Admin! Flag đăng nhập: <strong><?php echo htmlspecialchars($flag1); ?></strong>
    </div>
  <?php endif; ?>
  <section class="hero">
    <div>
      <span class="badge badge-info" style="margin-bottom:8px">✨ Bộ sưu tập F-CTF 2026</span>
      <h1>Mua sắm phụ kiện & công nghệ chất lượng cao</h1>
      <p>Khám phá 30+ sản phẩm thời trang, thiết bị số, khóa bảo mật và tài liệu học tập dành cho cộng đồng an toàn thông tin. Miễn phí vận chuyển cho đơn từ 300.000đ.</p>
      <?php if ($coupons): ?>
        <div class="voucher-bar">
          <?php foreach ($coupons as $cp): ?>
            <span class="voucher-pill">🎟️ Mã <strong><?php echo htmlspecialchars($cp['code']); ?></strong>: <?php echo htmlspecialchars($cp['description']); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!empty($_GET['added'])): ?>
    <div class="ok">Đã thêm sản phẩm vào giỏ hàng thành công! <a href="cart.php" style="font-weight:700;color:#15803d;margin-left:8px">Mở giỏ hàng →</a></div>
  <?php endif; ?>

  <!-- Thanh chon nhanh Danh muc (giu nguyen trang thai sap xep va tu khoa) -->
  <div class="cat-tabs">
    <a class="cat-tab <?php echo ($cat === '') ? 'active' : ''; ?>" href="index.php?<?php echo htmlspecialchars(build_filter_query(['cat' => null, 'page' => 1])); ?>">Tất cả sản phẩm</a>
    <?php foreach ($cats as $ct): ?>
      <a class="cat-tab <?php echo ($cat === $ct) ? 'active' : ''; ?>" href="index.php?<?php echo htmlspecialchars(build_filter_query(['cat' => $ct, 'page' => 1])); ?>">
        <?php echo htmlspecialchars($ct); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Thanh tim kiem & sap xep (tu dong cap nhat ngay khi chon danh muc hoac thu tu sap xep) -->
  <form class="filter-bar" method="get" action="index.php">
    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="🔍 Tìm tên sản phẩm, mô tả...">
    <select name="cat" onchange="this.form.submit()">
      <option value="">Tất cả danh mục</option>
      <?php foreach ($cats as $ct): ?>
        <option value="<?php echo htmlspecialchars($ct); ?>" <?php echo ($cat === $ct) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($ct); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="sort" onchange="this.form.submit()">
      <option value="default" <?php echo ($sort === 'default') ? 'selected' : ''; ?>>Sắp xếp: Mặc định</option>
      <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Giá: Thấp đến Cao</option>
      <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Giá: Cao đến Thấp</option>
      <option value="name_asc" <?php echo ($sort === 'name_asc') ? 'selected' : ''; ?>>Tên: A → Z</option>
      <option value="name_desc" <?php echo ($sort === 'name_desc') ? 'selected' : ''; ?>>Tên: Z → A</option>
    </select>
    <button type="submit">Lọc kết quả</button>
    <?php if ($q !== '' || $cat !== '' || $sort !== 'default'): ?>
      <a class="btn btn-secondary" href="index.php">Xóa lọc</a>
    <?php endif; ?>
  </form>
  <div id="searchSuggestBox" style="position:relative"></div>
  <script>
  // Goi y tim kiem truc tiep (live search) qua api_search.php
  (function () {
    var input = document.querySelector('.filter-bar input[name="q"]');
    if (!input) return;
    var box = document.getElementById('searchSuggestBox');
    var timer = null;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var val = input.value.trim();
      if (!val) { box.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch('api_search.php?q=' + encodeURIComponent(val))
          .then(function (r) { return r.json(); })
          .then(function (items) {
            if (!Array.isArray(items) || !items.length) { box.innerHTML = ''; return; }
            var html = '<div class="card" style="position:absolute;top:0;left:0;right:0;z-index:50;margin:0;padding:8px 12px;max-height:280px;overflow-y:auto">';
            items.forEach(function (it) {
              var id = parseInt(it.id, 10) || 0;
              var name = String(it.name ?? '').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
              var price = Number(it.price);
              var priceText = Number.isFinite(price) ? price.toLocaleString('vi-VN') + ' VND' : String(it.price ?? '').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
              html += '<a href="index.php?view=' + id + '" style="display:flex;justify-content:space-between;align-items:center;padding:8px 6px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);font-size:0.9rem">'
                    + '<span>' + name + '</span><strong style="color:var(--primary)">' + priceText + '</strong></a>';
            });
            html += '</div>';
            box.innerHTML = html;
          })
          .catch(function () {});
      }, 250);
    });
  })();
  </script>

  <?php if (!$products): ?>
    <div class="card" style="text-align:center;padding:40px 20px">
      <h3>Không tìm thấy sản phẩm phù hợp</h3>
      <p class="muted">Hãy thử từ khóa khác hoặc xem toàn bộ danh mục sản phẩm.</p>
      <a class="btn btn-secondary" href="index.php" style="margin-top:8px">Xem tất cả sản phẩm</a>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($products as $p): ?>
        <div class="card card-hover product-card">
          <div>
            <div style="position:relative">
              <a href="index.php?view=<?php echo (int)$p['id']; ?>" style="text-decoration:none;display:block">
                <?php echo render_product_thumb($p, false); ?>
              </a>
              <a class="wish-btn <?php echo isset($_SESSION['wishlist'][(int)$p['id']]) ? 'is-saved' : ''; ?>" aria-label="<?php echo isset($_SESSION['wishlist'][(int)$p['id']]) ? 'Bỏ yêu thích' : 'Thêm yêu thích'; ?>" title="<?php echo isset($_SESSION['wishlist'][(int)$p['id']]) ? 'Bỏ yêu thích' : 'Thêm yêu thích'; ?>" href="index.php?<?php echo htmlspecialchars(build_filter_query(['wish' => (int)$p['id'], 'page' => (int)$page])); ?>">♡</a>
            </div>
            <div class="product-cat"><?php echo htmlspecialchars($p['category'] ?? 'Phụ kiện'); ?></div>
            <a class="product-title" href="index.php?view=<?php echo (int)$p['id']; ?>">
              <?php echo htmlspecialchars($p['name']); ?>
            </a>
            <p class="muted" style="font-size:0.84rem;min-height:42px;margin:4px 0">
              <?php echo htmlspecialchars($p['description']); ?>
            </p>
          </div>

          <div>
            <div class="price-row">
              <span class="price-current"><?php echo number_format($p['price']); ?> VND</span>
              <?php if (!empty($p['old_price']) && $p['old_price'] > $p['price']): ?>
                <span class="price-old"><?php echo number_format($p['old_price']); ?>đ</span>
              <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px">
              <a class="btn btn-sm" style="flex:1" href="index.php?<?php echo htmlspecialchars(build_filter_query(['add' => (int)$p['id'], 'page' => (int)$page])); ?>">Thêm vào giỏ</a>
              <a class="btn btn-secondary btn-sm" href="index.php?view=<?php echo (int)$p['id']; ?>">Chi tiết</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Thanh phan trang (Trang 1, 2, 3...) -->
    <div class="pagination-wrap">
      <div class="muted">
        Hiển thị <strong><?php echo ($offset + 1); ?>–<?php echo min($totalItems, $offset + count($products)); ?></strong>
        trên tổng số <strong><?php echo $totalItems; ?></strong> sản phẩm (Trang <?php echo $page; ?>/<?php echo $totalPages; ?>)
      </div>

      <div class="pagination">
        <a class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars($buildPageUrl(max(1, $page - 1))); ?>">
          « Trước
        </a>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a class="page-link <?php echo ($i === $page) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($buildPageUrl($i)); ?>">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>
        <a class="page-link <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars($buildPageUrl(min($totalPages, $page + 1))); ?>">
          Sau »
        </a>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php layout_foot(); ?>