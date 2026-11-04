<?php
require 'db.php';
$c = db();
if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];

if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    unset($_SESSION['wishlist'][$id]);
    header('Location: wishlist.php?removed=1');
    exit;
}

$ids = array_values(array_filter(array_map('intval', array_keys($_SESSION['wishlist'])), function ($id) { return $id > 0; }));
$products = [];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $c->prepare("SELECT * FROM products WHERE id IN ($placeholders) ORDER BY name ASC");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

layout_head('Sản phẩm yêu thích - ShopLite');
?>
<div class="page-heading" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
  <div><div class="product-cat">BỘ SƯU TẬP CỦA BẠN</div><h1 style="margin-bottom:4px">♡ Sản phẩm yêu thích</h1><p class="muted">Lưu lại những món đồ bạn muốn xem hoặc mua sau.</p></div>
  <a class="btn btn-secondary" href="index.php">← Tiếp tục mua sắm</a>
</div>
<?php if (!empty($_GET['removed'])): ?><div class="ok">Đã xóa sản phẩm khỏi danh sách yêu thích.</div><?php endif; ?>
<?php if (!$products): ?>
  <div class="card" style="text-align:center;padding:48px 22px">
    <div style="font-size:3rem;margin-bottom:8px">♡</div>
    <h2>Danh sách đang trống</h2>
    <p class="muted">Nhấn biểu tượng trái tim ở sản phẩm để lưu vào đây.</p>
    <a class="btn" href="index.php" style="margin-top:12px">Khám phá sản phẩm</a>
  </div>
<?php else: ?>
  <div class="grid">
  <?php foreach ($products as $p): ?>
    <article class="card card-hover product-card">
      <a href="index.php?view=<?php echo (int)$p['id']; ?>" style="text-decoration:none;display:block"><?php echo render_product_thumb($p, false); ?></a>
      <div class="product-cat"><?php echo htmlspecialchars($p['category'] ?? 'Phụ kiện'); ?></div>
      <a class="product-title" href="index.php?view=<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></a>
      <p class="muted" style="font-size:.84rem;min-height:42px"><?php echo htmlspecialchars($p['description'] ?? ''); ?></p>
      <div class="price-row"><span class="price-current"><?php echo number_format((int)$p['price']); ?> VND</span></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
        <a class="btn btn-sm" style="flex:1" href="index.php?add=<?php echo (int)$p['id']; ?>">Thêm vào giỏ</a>
        <a class="btn btn-secondary btn-sm" href="wishlist.php?remove=<?php echo (int)$p['id']; ?>">Bỏ lưu</a>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>
