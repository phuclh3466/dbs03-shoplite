<?php
require 'db.php';
$c = db();

$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $submitted = true;

    // ===== LO HONG: SQL Injection - Time-based Blind (CWE-89) =====
    // De tranh "user enumeration" (biet email nao ton tai hay khong), trang
    // nay LUON tra ve 1 thong bao chung chung bat ke email co ton tai hay
    // khong, bat ke truy van loi hay khong (khac voi check_username.php -
    // o day KHONG CO su khac biet nao ve response de lam oracle boolean).
    // Nhung $email van duoc noi chuoi truc tiep vao SQL -> ke tan cong phai
    // dua vao THOI GIAN phan hoi de suy luan dung/sai (vi SQLite khong co
    // ham SLEEP() nhu MySQL, ky thuat thuc te la ep DB tinh toan nang bang
    // 1 truy van CTE de quy lon khi dieu kien dung, vd:
    //   ' OR (SELECT CASE WHEN (dieu_kien) THEN
    //         (SELECT COUNT(*) FROM (WITH RECURSIVE c(x) AS
    //           (SELECT 1 UNION ALL SELECT x+1 FROM c WHERE x<2000000)
    //           SELECT x FROM c))
    //         ELSE 0 END)=0 -- 
    // -> neu dieu_kien dung, phan hoi se cham han ro ret so voi khi sai.
    // Dong session truoc khi chay truy van de cac request Time-based Blind song song khong bi treo boi PHP session lock
    session_write_close();

    // Co lap pham vi Flag #5: cho phep do sqlite_master va doc bang flag_level5,
    // chan truy cap cheo sang mat khau user va cac bang flag cua cap do 1..4.
    if (!preg_match('/(password|orders|flag_level1|flag_level2|flag_level3|secrets)/i', $email)) {
        $sql = "SELECT id FROM users WHERE email = '" . $email . "'";
        try {
            $c->query($sql)->fetchAll();
        } catch (Throwable $e) {
            // nuot loi co y: khong de lo bat ky khac biet nao qua response
        }
    }
}

layout_head('ShopLite - Quên mật khẩu');
?>
<div class="card" style="max-width:560px;margin:0 auto">
  <h1>🔑 Quên mật khẩu</h1>
  <p class="muted">Nhập email đã đăng ký, chúng tôi sẽ gửi hướng dẫn khôi phục nếu email tồn tại trong hệ thống.</p>
  <form method="post">
    <label>Địa chỉ Email</label>
    <input type="email" name="email" placeholder="email@example.com" required>
    <button type="submit" class="btn btn-block">Gửi yêu cầu</button>
  </form>
  <?php if ($submitted): ?>
    <p class="ok" style="margin-top:14px">Nếu email tồn tại trong hệ thống, hướng dẫn khôi phục đã được gửi.</p>
  <?php endif; ?>
</div>
<?php layout_foot(); ?>