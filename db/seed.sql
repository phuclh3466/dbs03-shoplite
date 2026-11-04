-- ===== DBS-03: ShopLite - Unrestricted File Upload (Expanded 20 Products + Images + Pagination) =====
PRAGMA foreign_keys = ON;

CREATE TABLE users (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  username   TEXT UNIQUE NOT NULL,
  password   TEXT NOT NULL,
  role       TEXT NOT NULL DEFAULT 'user',
  full_name  TEXT DEFAULT '',
  email      TEXT DEFAULT '',
  phone      TEXT DEFAULT '',
  address    TEXT DEFAULT '',
  avatar     TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  name        TEXT NOT NULL,
  category    TEXT NOT NULL DEFAULT 'Phụ kiện',
  price       INTEGER NOT NULL,
  old_price   INTEGER DEFAULT 0,
  stock       INTEGER NOT NULL DEFAULT 50,
  badge       TEXT DEFAULT '',
  icon        TEXT DEFAULT 'shirt',
  image_url   TEXT DEFAULT '',
  description TEXT,
  specs       TEXT DEFAULT ''
);

CREATE TABLE reviews (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  user_id    INTEGER NOT NULL,
  username   TEXT NOT NULL,
  rating     INTEGER NOT NULL DEFAULT 5,
  comment    TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Moi tai khoan chi duoc danh gia mot lan cho moi san pham
CREATE UNIQUE INDEX IF NOT EXISTS idx_reviews_product_user ON reviews(product_id, user_id);

CREATE TABLE coupons (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  code             TEXT UNIQUE NOT NULL,
  discount_percent INTEGER NOT NULL,
  max_discount     INTEGER NOT NULL DEFAULT 50000,
  min_order        INTEGER NOT NULL DEFAULT 0,
  description      TEXT DEFAULT '',
  active           INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE orders (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id         INTEGER NOT NULL,
  recipient_name  TEXT NOT NULL DEFAULT '',
  phone           TEXT NOT NULL DEFAULT '',
  address         TEXT NOT NULL DEFAULT '',
  note            TEXT DEFAULT '',
  payment_method  TEXT NOT NULL DEFAULT 'COD',
  coupon_code     TEXT DEFAULT '',
  discount_amount INTEGER NOT NULL DEFAULT 0,
  subtotal        INTEGER NOT NULL DEFAULT 0,
  total           INTEGER NOT NULL,
  status          TEXT NOT NULL DEFAULT 'Chờ xử lý',
  items           TEXT NOT NULL,
  created_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, role, full_name, email, phone, address) VALUES
  ('admin', 'Adm1n_Sh0p_2024!', 'admin', 'Quản trị viên ShopLite', 'admin@shoplite.local', '0901234567', 'Tòa nhà F-CTF Tower, Quận Cầu Giấy, Hà Nội'),
  ('user1', 'password123', 'user', 'Nguyễn Văn An', 'nguyenvanan@example.com', '0987654321', 'Số 12 Duy Tân, Cầu Giấy, Hà Nội'),
   ('khoi.dang', 'password123', 'user', 'Đặng Minh Khôi', 'dangminhkhoi@example.com', '0956781234', 'Số 101 Hoàng Hoa Thám, Ba Đình, Hà Nội'),
  ('lanvu92', 'password123', 'user', 'Vũ Thị Lan', 'vuthilan@example.com', '0967812345', 'Số 56 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh'),
  ('huybq', 'password123', 'user', 'Bùi Quốc Huy', 'buiquochuy@example.com', '0978123456', 'Số 23 Trần Hưng Đạo, Hải Châu, Đà Nẵng'),
  ('thungo', 'password123', 'user', 'Ngô Thị Thu', 'ngothithu@example.com', '0989234567', 'Số 9 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội'),
  ('tungdo88', 'password123', 'user', 'Đỗ Văn Tùng', 'dovantung@example.com', '0990345678', 'Số 18 Phan Đình Phùng, Huế'),
  ('maihoang', 'password123', 'user', 'Hoàng Thị Mai', 'hoangthimai@example.com', '0901456789', 'Số 77 Cách Mạng Tháng 8, Quận 3, TP. Hồ Chí Minh'),
  ('ducanh.phan', 'password123', 'user', 'Phan Đức Anh', 'phanducanh@example.com', '0912567890', 'Số 34 Bạch Đằng, Hồng Bàng, Hải Phòng'),
  ('huongnt', 'password123', 'user', 'Nguyễn Thị Hương', 'nguyenthihuong@example.com', '0923678901', 'Số 5 Lê Duẩn, Vinh, Nghệ An'),
  ('sontrinh', 'password123', 'user', 'Trịnh Công Sơn', 'trinhcongson@example.com', '0934789012', 'Số 12 Hùng Vương, Việt Trì, Phú Thọ'),
  ('ngocly95', 'password123', 'user', 'Lý Thị Ngọc', 'lythingoc@example.com', '0945890123', 'Số 66 Điện Biên Phủ, Bình Thạnh, TP. Hồ Chí Minh'),
  ('haimai', 'password123', 'user', 'Mai Văn Hải', 'maivanhai@example.com', '0956901234', 'Số 29 Quang Trung, Thanh Khê, Đà Nẵng'),
  ('thaodinh', 'password123', 'user', 'Đinh Thị Thảo', 'dinhthithao@example.com', '0967012345', 'Số 41 Nguyễn Văn Cừ, Long Biên, Hà Nội'),
  ('quanta.90', 'password123', 'user', 'Tạ Minh Quân', 'taminhquan@example.com', '0978123450', 'Số 15 Trần Phú, Nha Trang, Khánh Hòa'),
  ('yenluong', 'password123', 'user', 'Lương Thị Yến', 'luongthiyen@example.com', '0989234561', 'Số 88 Lê Hồng Phong, Quy Nhơn, Bình Định'),
  ('phuchv', 'password123', 'user', 'Hồ Văn Phúc', 'hovanphuc@example.com', '0990345672', 'Số 3 Nguyễn Chí Thanh, Đống Đa, Hà Nội');

INSERT INTO products (name, category, price, old_price, stock, badge, icon, description, specs) VALUES
  ('Áo thun F-CTF Pro', 'Thời trang', 150000, 190000, 45, 'Bán chạy', 'shirt', 'Áo thun cotton 100% co giãn 4 chiều, in logo F-CTF sắc nét, thoáng mát cả ngày.', 'Chất liệu: Cotton Compact 250gsm | Size: S, M, L, XL | Màu sắc: Trắng / Đen Navy'),
  ('Mũ lưỡi trai Hacker Edition', 'Phụ kiện', 120000, 150000, 30, 'Mới', 'cap', 'Mũ lưỡi trai kaki cao cấp thêu nổi phong cách Cyber Security, khóa đồng điều chỉnh size.', 'Chất liệu: Kaki Cotton Hàn Quốc | Chu vi: 54-60cm | Kiểu dáng: Baseball Cap'),
  ('Áo hoodie F-CTF Cyber', 'Thời trang', 350000, 420000, 25, 'Hot', 'hoodie', 'Áo hoodie nỉ bông dày dặn giữ ấm mùa đông, form oversize trẻ trung năng động.', 'Chất liệu: Nỉ bông 380gsm | Kiểu dáng: Oversize Unisex | Túi: Kangaroo lớn'),
  ('Bình nước giữ nhiệt Inox 304', 'Phụ kiện', 90000, 115000, 60, 'Tiết kiệm', 'bottle', 'Bình inox 304 dung tích 500ml giữ nhiệt nóng/lạnh lên đến 12 giờ, nắp chống tràn.', 'Dung tích: 500ml | Chất liệu: Inox 304 an toàn | Giữ nhiệt: 12h - 18h'),
  ('Balo chống sốc Laptop 15.6"', 'Phụ kiện', 450000, 550000, 20, 'Cao cấp', 'bag', 'Balo vải Oxford trượt nước, ngăn chống sốc dày dặn kèm cổng sạc USB tiện lợi.', 'Kích thước: 44x31x15cm | Ngăn laptop: Tối đa 15.6 inch | Chống thấm: Chuẩn IPX4'),
  ('Bàn phím cơ Mini 68 Phím', 'Thiết bị số', 690000, 850000, 15, 'Hot', 'keyboard', 'Bàn phím cơ layout 68 phím nhỏ gọn cho lập trình viên, hotswap 5 pin, LED RGB.', 'Switch: Red/Brown Tuyến tính | Kết nối: Type-C tháo rời | Keycap: PBT Double-shot'),
  ('Chuột không dây Silent Pro', 'Thiết bị số', 280000, 330000, 35, 'Mới', 'mouse', 'Chuột công thái học click siêu êm không gây tiếng ồn, kết nối kép Bluetooth & 2.4GHz.', 'DPI: 800-1600-2400 | Pin: Sạc Type-C 500mAh | Trọng lượng: 75g siêu nhẹ'),
  ('Bộ Sticker Security & Dev (50 hình)', 'Phụ kiện', 45000, 60000, 100, 'Yêu thích', 'sticker', 'Bộ 50 sticker PVC chống nước chủ đề Linux, CTF, Networking & Programming dán laptop.', 'Số lượng: 50 miếng/bộ | Chất liệu: Decal PVC cán mờ chống nước | Kích thước: 4-7cm'),
  ('Áo khoác Bomber CyberOps', 'Thời trang', 480000, 580000, 18, 'Mới', 'bomber', 'Áo khoác gió Bomber 2 lớp cản gió chống nước nhẹ, khóa kéo kim loại kèm túi tay tiện dụng.', 'Chất liệu: Dù Polyester 2 lớp | Size: M, L, XL | Màu sắc: Đen tuyền / Xanh Rêu'),
  ('Tai nghe chụp tai Gaming 7.1', 'Thiết bị số', 590000, 720000, 22, 'Bán chạy', 'headset', 'Tai nghe giả lập âm thanh vòm 7.1 sống động, đệm tai da PU êm ái, micro khử ồn.', 'Driver: 50mm Neodymium | Kết nối: USB / 3.5mm | Micro: Khử ồn đa hướng'),
  ('Khóa bảo mật cứng FIDO2 USB-C', 'Thiết bị số', 850000, 990000, 12, 'Cao cấp', 'yubikey', 'Khóa xác thực phần cứng chuẩn FIDO2/WebAuthn chống Phishing tuyệt đối cho tài khoản.', 'Chuẩn: FIDO2, U2F, TOTP | Giao tiếp: USB-C & NFC | Chống nước: IP68'),
  ('Lót chuột cỡ lớn World Map XXL', 'Phụ kiện', 135000, 175000, 50, 'Giảm giá', 'mousepad', 'Bàn di chuột kích thước lớn 90x40cm bo viền chắc chắn, bề mặt vải Speed di chuột mượt mà.', 'Kích thước: 900x400x4mm | Đế: Cao su tự nhiên chống trượt | Viền: Bo chỉ dù'),
  ('Áo Polo DevSecOps Edition', 'Thời trang', 220000, 270000, 40, 'Yêu thích', 'polo', 'Áo thun có cổ lịch sự chất vải cá sấu Poly thoáng khí, thêu logo ngực tinh tế.', 'Chất liệu: Cá sấu Poly co giãn | Form: Regular Fit | Size: S, M, L, XL, XXL'),
  ('Hub chuyển đổi Type-C 7-in-1', 'Thiết bị số', 390000, 460000, 28, 'Hot', 'hub', 'Bộ mở rộng cổng USB-C ra HDMI 4K, 3 cổng USB 3.0, khe thẻ nhớ SD/TF và sạc PD 100W.', 'Vỏ: Hợp kim nhôm tản nhiệt | HDMI: 4K@60Hz | Sạc PD: Tối đa 100W'),
  ('Giá đỡ Laptop Nhôm CNC', 'Phụ kiện', 210000, 260000, 32, 'Bán chạy', 'stand', 'Chân đế nâng laptop hợp kim nhôm nguyên khối, điều chỉnh 6 nấc độ cao giúp tản nhiệt.', 'Chất liệu: Nhôm Anodized | Tương thích: Laptop 11 - 17 inch | Trọng lượng: 260g'),
  ('Cốc sứ đổi màu Binary Code', 'Phụ kiện', 85000, 110000, 55, 'Quà tặng', 'mug', 'Cốc sứ tráng men cao cấp tự động hiện dãy mã nhị phân & thông điệp ẩn khi rót nước nóng.', 'Dung tích: 380ml | Chất liệu: Gốm sứ Bát Tràng tráng men nhiệt'),
  ('Sổ tay Pentest Checklist A5', 'Sách & Lab', 65000, 85000, 80, 'Mới', 'notebook', 'Sổ tay bìa cứng chấm bi (Dot Grid) kèm phụ lục tổng hợp lệnh Linux & OWASP Top 10.', 'Số trang: 160 trang giấy chống lóa 100gsm | Khổ: A5 (14.8 x 21cm)'),
  ('Sách Web Security & CTF Handbook', 'Sách & Lab', 240000, 290000, 25, 'Bán chạy', 'book', 'Cẩm nang thực hành khai thác và phòng chống lỗ hổng Web từ cơ bản đến nâng cao cho sinh viên.', 'Số trang: 380 trang in màu | Chủ đề: SQLi, XSS, File Upload, SSRF, Deserialization'),
  ('USB Dual OTG 128GB Tốc độ cao', 'Thiết bị số', 260000, 310000, 38, 'Tiết kiệm', 'usb', 'USB 2 đầu cắm (USB-A 3.2 & Type-C) vỏ kim loại xoay 360 độ, chép bộ máy ảo cực nhanh.', 'Dung lượng: 128GB | Tốc độ đọc: Lên đến 150MB/s | Chuẩn: USB 3.2 Gen 1'),
  ('Túi đeo chéo Tactical Crossbody', 'Thời trang', 290000, 350000, 24, 'Mới', 'sling', 'Túi đeo chéo vải Cordura chống thấm nước, nhiều ngăn nhỏ đựng phụ kiện công nghệ hằng ngày.', 'Kích thước: 30x18x9cm | Chất liệu: Cordura 1000D | Khóa: Nam châm thao tác nhanh');

-- ===== 12 san pham bo sung (id 21-32) =====
INSERT INTO products (name, category, price, old_price, stock, badge, icon, description, specs) VALUES
  ('Sách Python cho Pentester & Automation', 'Sách & Lab', 210000, 260000, 30, 'Mới', 'pybook', 'Hướng dẫn viết script Python tự động hóa quét cổng, brute-force có kiểm soát và phân tích log phục vụ kiểm thử xâm nhập.', 'Số trang: 320 trang | Chủ đề: Socket, Requests, Scapy, Automation | Kèm mã nguồn bài lab'),
  ('Webcam Full HD 1080p có nắp che riêng tư', 'Thiết bị số', 420000, 520000, 26, 'Bảo mật', 'webcam', 'Webcam 1080p 30fps tích hợp micro khử ồn và nắp trượt che ống kính, yên tâm khi học online và họp trực tuyến.', 'Độ phân giải: 1920x1080 | Kết nối: USB-A cắm là chạy | Góc nhìn: 90 độ | Nắp che vật lý'),
  ('Pin dự phòng 20.000mAh sạc nhanh PD 22.5W', 'Thiết bị số', 490000, 590000, 34, 'Bán chạy', 'powerbank', 'Pin sạc dự phòng dung lượng lớn, màn hình LED hiển thị phần trăm pin, sạc đồng thời 2 thiết bị.', 'Dung lượng: 20.000mAh | Cổng: USB-C PD 22.5W + USB-A | Vỏ: Nhựa chống cháy'),
  ('Củ sạc nhanh GaN 65W 2 cổng', 'Thiết bị số', 330000, 399000, 40, 'Hot', 'charger', 'Củ sạc GaN nhỏ gọn sạc nhanh laptop, điện thoại và máy tính bảng chỉ với một adapter duy nhất.', 'Công suất: 65W | Cổng: 1 USB-C + 1 USB-A | Công nghệ: GaN III, PD 3.0, PPS'),
  ('Cáp sạc Type-C to Type-C 100W bọc dù 1.5m', 'Phụ kiện', 120000, 150000, 75, 'Tiết kiệm', 'cable', 'Cáp bọc dù chống rối, đầu nối kim loại bền chắc, hỗ trợ sạc nhanh 100W và truyền dữ liệu tốc độ cao.', 'Chiều dài: 1.5m | Công suất: tối đa 100W | Truyền dữ liệu: 480Mbps'),
  ('Loa Bluetooth mini chống nước IPX6', 'Thiết bị số', 360000, 450000, 29, 'Mới', 'speaker', 'Loa nhỏ gọn âm thanh 360 độ, chống nước chống bụi, pin dùng liên tục tới 12 giờ, thích hợp mang đi cafe hay picnic.', 'Công suất: 10W | Bluetooth: 5.3 | Pin: 12 giờ | Chuẩn: IPX6'),
  ('Tai nghe True Wireless chống ồn ANC', 'Thiết bị số', 650000, 790000, 22, 'Cao cấp', 'earbuds', 'Tai nghe không dây nhét tai chống ồn chủ động, hộp sạc nhỏ gọn, độ trễ thấp cho cả nghe nhạc lẫn chơi game.', 'Chống ồn: ANC chủ động | Pin: 7 giờ (tai nghe) + 28 giờ (kèm hộp) | Bluetooth 5.3'),
  ('Đèn bàn LED chống lóa 3 chế độ ánh sáng', 'Phụ kiện', 280000, 340000, 33, 'Yêu thích', 'lamp', 'Đèn bàn gập linh hoạt, ánh sáng chống lóa bảo vệ mắt khi học và làm việc khuya, điều chỉnh độ sáng cảm ứng.', 'Chế độ: Ấm / Trung tính / Trắng | Độ sáng: 5 mức | Nguồn: USB-C 5V'),
  ('Kit Raspberry Pi 4 (4GB) học IoT & Pentest', 'Sách & Lab', 1590000, 1790000, 10, 'Cao cấp', 'board', 'Bộ kit máy tính nhúng Raspberry Pi 4 đầy đủ nguồn, vỏ, thẻ nhớ sẵn hệ điều hành để dựng lab IoT và thực hành bảo mật mạng.', 'RAM: 4GB | CPU: Quad-core 1.5GHz | Cổng: 2x micro-HDMI, USB 3.0, Gigabit LAN | Kèm thẻ nhớ 32GB'),
  ('Màn hình 24 inch Full HD IPS viền mỏng', 'Thiết bị số', 2590000, 2990000, 8, 'Giảm giá', 'monitor', 'Màn hình IPS 24 inch viền mỏng, màu sắc chính xác, góc nhìn rộng, lý tưởng để mở nhiều cửa sổ code và terminal cùng lúc.', 'Tấm nền: IPS 24 inch | Độ phân giải: 1920x1080 | Tần số quét: 75Hz | Cổng: HDMI, VGA'),
  ('Dây đeo thẻ Lanyard F-CTF kèm thẻ nhựa', 'Phụ kiện', 35000, 50000, 120, 'Quà tặng', 'lanyard', 'Dây đeo thẻ in logo F-CTF kèm bao thẻ nhựa trong suốt, phù hợp đeo khi thi đấu CTF onsite và sự kiện.', 'Chất liệu: Polyester | Chiều dài: 90cm | Khóa: Móc kim loại xoay'),
  ('Tất cotton Binary Code (set 3 đôi)', 'Thời trang', 79000, 99000, 70, 'Mới', 'socks', 'Set 3 đôi tất cotton co giãn in họa tiết mã nhị phân, thấm hút mồ hôi tốt, êm chân cả ngày dài.', 'Chất liệu: Cotton 80% | Size: Free size 38-43 | Số lượng: 3 đôi/set');

INSERT INTO reviews (product_id, user_id, username, rating, comment, created_at) VALUES
  (1, 2, 'user1', 5, 'Chất vải cực kỳ mát và dày dặn, hình in sắc nét không bị bong tróc sau khi giặt!', '2026-10-20 09:30:00'),
  (3, 2, 'user1', 5, 'Form áo rộng rãi mặc rất thích, nỉ ấm áp, giao hàng nhanh.', '2026-10-22 14:15:00'),
  (6, 1, 'admin', 5, 'Gõ rất đầm tay, layout 68 phím mang đi cafe hay CTF onsite đều gọn.', '2026-10-25 18:00:00'),
  (11, 1, 'admin', 5, 'Khóa FIDO2 nhận cực nhạy cả trên laptop lẫn NFC điện thoại.', '2026-10-26 11:20:00'),
  (18, 2, 'user1', 5, 'Sách trình bày dễ hiểu, có ví dụ bài tập lab thực tế rất sát đề thi.', '2026-10-27 16:40:00');

INSERT INTO coupons (code, discount_percent, max_discount, min_order, description, active) VALUES
  ('FCTF10', 10, 50000, 0, 'Giảm 10% tối đa 50.000đ cho mọi đơn hàng', 1),
  ('SHOPLITE20', 20, 100000, 300000, 'Giảm 20% tối đa 100.000đ cho đơn từ 300.000đ', 1),
  ('HELLO5', 5, 30000, 100000, 'Giảm 5% tối đa 30.000đ cho đơn từ 100.000đ', 1);

INSERT INTO orders (user_id, recipient_name, phone, address, note, payment_method, coupon_code, discount_amount, subtotal, total, status, items, created_at) VALUES
  (2, 'Nguyễn Văn An', '0987654321', 'Số 12 Duy Tân, Cầu Giấy, Hà Nội', 'Giao giờ hành chính giúp mình', 'COD', 'FCTF10', 27000, 270000, 243000, 'Hoàn thành', '[{"id":1,"name":"Áo thun F-CTF Pro","qty":1,"price":150000},{"id":2,"name":"Mũ lưỡi trai Hacker Edition","qty":1,"price":120000}]', '2026-10-24 10:15:00'),
  ((SELECT id FROM users WHERE username='lanvu92'), 'Vũ Thị Lan', '0967812345', 'Số 56 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 'Giao trước 18h giúp mình', 'COD', '', 0, 350000, 350000, 'Chờ xử lý', '[{"id": 3, "name": "Áo hoodie F-CTF Cyber", "qty": 1, "price": 350000}]', '2026-10-26 08:40:00'),
  ((SELECT id FROM users WHERE username='huybq'), 'Bùi Quốc Huy', '0978123456', 'Số 23 Trần Hưng Đạo, Hải Châu, Đà Nẵng', '', 'BANK', 'FCTF10', 69000, 690000, 621000, 'Đang giao', '[{"id": 6, "name": "Bàn phím cơ Mini 68 Phím", "qty": 1, "price": 690000}]', '2026-10-26 10:05:00'),
  ((SELECT id FROM users WHERE username='thungo'), 'Ngô Thị Thu', '0989234567', 'Số 9 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội', 'Gọi trước khi giao', 'COD', '', 0, 225000, 250000, 'Hoàn thành', '[{"id": 4, "name": "Bình nước giữ nhiệt Inox 304", "qty": 2, "price": 90000}, {"id": 8, "name": "Bộ Sticker Security & Dev (50 hình)", "qty": 1, "price": 45000}]', '2026-10-20 15:22:00'),
  ((SELECT id FROM users WHERE username='tungdo88'), 'Đỗ Văn Tùng', '0990345678', 'Số 18 Phan Đình Phùng, Huế', '', 'WALLET', '', 0, 280000, 305000, 'Đã hủy', '[{"id": 7, "name": "Chuột không dây Silent Pro", "qty": 1, "price": 280000}]', '2026-10-18 09:10:00'),
  ((SELECT id FROM users WHERE username='maihoang'), 'Hoàng Thị Mai', '0901456789', 'Số 77 Cách Mạng Tháng 8, Quận 3, TP. Hồ Chí Minh', 'Giao giờ hành chính', 'BANK', 'SHOPLITE20', 88000, 450000, 362000, 'Hoàn thành', '[{"id": 5, "name": "Balo chống sốc Laptop 15.6 inch", "qty": 1, "price": 450000}]', '2026-10-21 13:50:00'),
  ((SELECT id FROM users WHERE username='ducanh.phan'), 'Phan Đức Anh', '0912567890', 'Số 34 Bạch Đằng, Hồng Bàng, Hải Phòng', '', 'COD', '', 0, 300000, 300000, 'Đang giao', '[{"id": 1, "name": "Áo thun F-CTF Pro", "qty": 2, "price": 150000}]', '2026-10-27 09:00:00'),
  ((SELECT id FROM users WHERE username='huongnt'), 'Nguyễn Thị Hương', '0923678901', 'Số 5 Lê Duẩn, Vinh, Nghệ An', 'Để hàng ở bảo vệ', 'COD', 'HELLO5', 13500, 210000, 221500, 'Chờ xử lý', '[{"id": 2, "name": "Mũ lưỡi trai Hacker Edition", "qty": 1, "price": 120000}, {"id": 4, "name": "Bình nước giữ nhiệt Inox 304", "qty": 1, "price": 90000}]', '2026-10-27 11:35:00'),
  ((SELECT id FROM users WHERE username='sontrinh'), 'Trịnh Công Sơn', '0934789012', 'Số 12 Hùng Vương, Việt Trì, Phú Thọ', '', 'WALLET', '', 0, 135000, 160000, 'Hoàn thành', '[{"id": 8, "name": "Bộ Sticker Security & Dev (50 hình)", "qty": 3, "price": 45000}]', '2026-10-15 16:40:00'),
  ((SELECT id FROM users WHERE username='ngocly95'), 'Lý Thị Ngọc', '0945890123', 'Số 66 Điện Biên Phủ, Bình Thạnh, TP. Hồ Chí Minh', '', 'BANK', 'FCTF10', 15000, 500000, 485000, 'Đang giao', '[{"id": 3, "name": "Áo hoodie F-CTF Cyber", "qty": 1, "price": 350000}, {"id": 1, "name": "Áo thun F-CTF Pro", "qty": 1, "price": 150000}]', '2026-10-27 14:12:00'),
  ((SELECT id FROM users WHERE username='haimai'), 'Mai Văn Hải', '0956901234', 'Số 29 Quang Trung, Thanh Khê, Đà Nẵng', 'Giao vào cuối tuần', 'COD', '', 0, 970000, 970000, 'Chờ xử lý', '[{"id": 6, "name": "Bàn phím cơ Mini 68 Phím", "qty": 1, "price": 690000}, {"id": 7, "name": "Chuột không dây Silent Pro", "qty": 1, "price": 280000}]', '2026-10-28 08:05:00'),
  ((SELECT id FROM users WHERE username='thaodinh'), 'Đinh Thị Thảo', '0967012345', 'Số 41 Nguyễn Văn Cừ, Long Biên, Hà Nội', '', 'WALLET', '', 0, 240000, 265000, 'Đã hủy', '[{"id": 2, "name": "Mũ lưỡi trai Hacker Edition", "qty": 2, "price": 120000}]', '2026-10-19 12:00:00'),
  ((SELECT id FROM users WHERE username='phuchv'), 'Hồ Văn Phúc', '0990345672', 'Số 3 Nguyễn Chí Thanh, Đống Đa, Hà Nội', 'Giao buổi tối', 'COD', 'SHOPLITE20', 45000, 450000, 405000, 'Hoàn thành', '[{"id": 5, "name": "Balo chống sốc Laptop 15.6 inch", "qty": 1, "price": 450000}]', '2026-10-16 19:30:00'),
  -- Don hang cua admin (khong con chua flag, IDOR da duoc va o order_view.php)
  ((SELECT id FROM users WHERE username='admin'), 'Quản trị viên ShopLite', '0901234567', 'Tòa nhà F-CTF Tower, Quận Cầu Giấy, Hà Nội', 'Đơn hàng thiết bị học tập nội bộ', 'BANK', '', 0, 1590000, 1590000, 'Hoàn thành', '[{"id":27,"name":"Kit Raspberry Pi 4 (4GB) học IoT & Pentest","qty":1,"price":1590000}]', '2026-10-28 23:59:00');

-- ===== Bang "secrets" (DBS-04: SQL Injection) =====
-- Bang gia lap du lieu nhay cam noi bo, chi truy xuat duoc qua UNION-based SQLi
-- tu api_search.php (xem file do).
CREATE TABLE secrets (
  id    INTEGER PRIMARY KEY AUTOINCREMENT,
  label TEXT NOT NULL,
  value TEXT NOT NULL
);

INSERT INTO secrets (label, value) VALUES
  ('sqli_flag', 'FLAG{un1on_s3lect_sqlite_inj3ction}');

-- ===== Bang "flag_level1" (Flag #1: Authentication Bypass qua login.php) =====
CREATE TABLE flag_level1 (
  id     INTEGER PRIMARY KEY AUTOINCREMENT,
  secret TEXT NOT NULL
);
INSERT INTO flag_level1 (secret) VALUES
  ('FLAG{4uth_byp4ss_sql1_cl4ssic}');

-- ===== Bang "flag_level2" (Flag #2: UNION-based qua track_order.php) =====
CREATE TABLE flag_level2 (
  id    INTEGER PRIMARY KEY AUTOINCREMENT,
  label TEXT NOT NULL,
  value TEXT NOT NULL
);
INSERT INTO flag_level2 (label, value) VALUES
  ('track_order_flag', 'FLAG{un1on_tra_cuu_kh0ng_x4c_th1c}');

-- ===== Bang "flag_level3" (Flag #3: Boolean-based Blind qua check_username.php) =====
CREATE TABLE flag_level3 (
  id     INTEGER PRIMARY KEY AUTOINCREMENT,
  secret TEXT NOT NULL
);
INSERT INTO flag_level3 (secret) VALUES
  ('FLAG{b00lean_bl1nd_tung_ky_tu_mot}');

-- ===== Bang "flag_level5" (Flag #5: Time-based Blind qua forgot_password.php) =====
CREATE TABLE flag_level5 (
  id     INTEGER PRIMARY KEY AUTOINCREMENT,
  secret TEXT NOT NULL
);
INSERT INTO flag_level5 (secret) VALUES
  ('FLAG{t1me_b4sed_bl1nd_kh0ng_c0_sleep}');

