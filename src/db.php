<?php
session_start();

function db() {
    // SQLite: file DB nam trong /tmp (ghi duoc tren read-only rootfs)
    static $c = null;
    if ($c === null) {
        $c = new PDO('sqlite:/tmp/app.db');
        $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $c->exec('PRAGMA busy_timeout = 5000');
        if (method_exists($c, 'sqliteCreateFunction')) {
            $c->sqliteCreateFunction('vn_sort', 'vn_sort_key', 1);
        }
    }
    return $c;
}

function base_prefix() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return (strpos($script, '/admin/') !== false) ? '../' : '';
}

function require_login() {
    if (empty($_SESSION['uid'])) {
        $b = base_prefix();
        header('Location: ' . $b . 'login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        layout_head('403 - Từ chối truy cập');
        echo '<div class="card" style="text-align:center;padding:48px 24px">';
        echo '<div style="font-size:48px;margin-bottom:12px">🔒</div>';
        echo '<h2 style="margin:0 0 8px;color:#dc2626">Bạn không có quyền truy cập trang này</h2>';
        echo '<p class="muted">Khu vực này chỉ dành riêng cho Quản trị viên hệ thống (Admin).</p>';
        echo '<a class="btn" href="' . base_prefix() . 'index.php" style="margin-top:12px">Quay về trang chủ</a>';
        echo '</div>';
        layout_foot();
        exit;
    }
}

function wishlist_count() {
    return (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) ? count($_SESSION['wishlist']) : 0;
}

function cart_count() {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

function vn_sort_key($str) {
    $s = mb_strtolower(trim((string)$str), 'UTF-8');
    // Chuyen cac nhom nguyen am tieng Viet va chữ đ ve dang chuan de sap xep A -> Z dung bang chu cai tieng Viet
    $map = [
        '/[àáảãạ]/u' => 'a1', '/[ăằắẳẵặ]/u' => 'a2', '/[âầấẩẫậ]/u' => 'a3',
        '/đ/u' => 'd1',
        '/[èéẻẽẹ]/u' => 'e1', '/[êềếểễệ]/u' => 'e2',
        '/[ìíỉĩị]/u' => 'i1',
        '/[òóỏõọ]/u' => 'o1', '/[ôồốổỗộ]/u' => 'o2', '/[ơờớởỡợ]/u' => 'o3',
        '/[ùúủũụ]/u' => 'u1', '/[ưừứửữự]/u' => 'u2',
        '/[ỳýỷỹỵ]/u' => 'y1',
    ];
    foreach ($map as $pattern => $rep) {
        $s = preg_replace($pattern, $rep, $s);
    }
    return $s;
}

function product_svg($icon = 'box') {
    $palettes = [
        'shirt'     => ['#f8fafc', '#e2e8f0', '#1e3a8a', '#2563eb'],
        'polo'      => ['#f0f9ff', '#e0f2fe', '#0f172a', '#0284c7'],
        'cap'       => ['#f8fafc', '#e2e8f0', '#1e293b', '#334155'],
        'hoodie'    => ['#f8fafc', '#e2e8f0', '#1f2937', '#475569'],
        'bomber'    => ['#f7fee7', '#ecfccb', '#273818', '#3f6212'],
        'bottle'    => ['#f0fdfa', '#ccfbf1', '#0f766e', '#14b8a6'],
        'bag'       => ['#f8fafc', '#e2e8f0', '#1e293b', '#334155'],
        'sling'     => ['#f8fafc', '#e2e8f0', '#0f172a', '#334155'],
        'keyboard'  => ['#f8fafc', '#e2e8f0', '#0f172a', '#f97316'],
        'mouse'     => ['#f8fafc', '#e2e8f0', '#0f172a', '#22c55e'],
        'headset'   => ['#f8fafc', '#e2e8f0', '#0f172a', '#6366f1'],
        'yubikey'   => ['#fefce8', '#fef3c7', '#18181b', '#eab308'],
        'usb'       => ['#f8fafc', '#e2e8f0', '#334155', '#0ea5e9'],
        'hub'       => ['#f8fafc', '#e2e8f0', '#334155', '#64748b'],
        'stand'     => ['#f8fafc', '#e2e8f0', '#475569', '#94a3b8'],
        'mousepad'  => ['#f8fafc', '#e2e8f0', '#0f172a', '#10b981'],
        'sticker'   => ['#fff7ed', '#ffedd5', '#ea580c', '#3b82f6'],
        'book'      => ['#eff6ff', '#dbeafe', '#1e3a8a', '#2563eb'],
        'notebook'  => ['#fffbeb', '#fef3c7', '#78350f', '#d97706'],
        'pybook'    => ['#fefce8', '#dbeafe', '#1e40af', '#eab308'],
        'mug'       => ['#f8fafc', '#e2e8f0', '#1e293b', '#22c55e'],
        'webcam'    => ['#f8fafc', '#e2e8f0', '#0f172a', '#38bdf8'],
        'powerbank' => ['#f8fafc', '#e2e8f0', '#0f172a', '#10b981'],
        'charger'   => ['#f8fafc', '#e2e8f0', '#475569', '#f59e0b'],
        'cable'     => ['#f8fafc', '#e2e8f0', '#1e293b', '#f97316'],
        'speaker'   => ['#f0f9ff', '#e0f2fe', '#0f172a', '#06b6d4'],
        'earbuds'   => ['#f8fafc', '#e2e8f0', '#334155', '#38bdf8'],
        'lamp'      => ['#fefce8', '#fef3c7', '#334155', '#facc15'],
        'board'     => ['#f0fdf4', '#dcfce7', '#14532d', '#16a34a'],
        'monitor'   => ['#eff6ff', '#dbeafe', '#0f172a', '#3b82f6'],
        'lanyard'   => ['#fef2f2', '#fee2e2', '#1e3a8a', '#ef4444'],
        'socks'     => ['#f0fdfa', '#ccfbf1', '#1e293b', '#14b8a6'],
    ];
    $p = $palettes[$icon] ?? ['#f8fafc', '#e2e8f0', '#1e3a8a', '#2563eb'];
    [$bg1, $bg2, $cDark, $cMain] = $p;
    $uid = preg_replace('/[^a-z0-9]/i', '', (string)$icon);

    $defs = "<linearGradient id='bg_{$uid}' x1='0' y1='0' x2='0' y2='1'>"
          . "<stop offset='0%' stop-color='{$bg1}'/><stop offset='100%' stop-color='{$bg2}'/>"
          . "</linearGradient>"
          . "<linearGradient id='main_{$uid}' x1='0.15' y1='0' x2='0.85' y2='1'>"
          . "<stop offset='0%' stop-color='{$cMain}'/><stop offset='100%' stop-color='{$cDark}'/>"
          . "</linearGradient>"
          . "<linearGradient id='metal_{$uid}' x1='0' y1='0' x2='1' y2='0'>"
          . "<stop offset='0%' stop-color='#cbd5e1'/><stop offset='35%' stop-color='#f8fafc'/>"
          . "<stop offset='65%' stop-color='#94a3b8'/><stop offset='100%' stop-color='#e2e8f0'/>"
          . "</linearGradient>"
          . "<linearGradient id='dark_{$uid}' x1='0' y1='0' x2='1' y2='1'>"
          . "<stop offset='0%' stop-color='#334155'/><stop offset='50%' stop-color='#1e293b'/><stop offset='100%' stop-color='#0f172a'/>"
          . "</linearGradient>"
          . "<radialGradient id='shadow_{$uid}' cx='50%' cy='50%' r='50%'>"
          . "<stop offset='0%' stop-color='#0f172a' stop-opacity='0.24'/>"
          . "<stop offset='70%' stop-color='#0f172a' stop-opacity='0.08'/>"
          . "<stop offset='100%' stop-color='#0f172a' stop-opacity='0'/>"
          . "</radialGradient>";

    $shadow = "<ellipse cx='100' cy='166' rx='56' ry='9' fill='url(#shadow_{$uid})'/>";

    switch ($icon) {
        case 'shirt':
            // Ao thun cotton co tron 3D co nep vai, bo co va hinh in nguc
            $inner = $shadow
                   . "<path d='M68 38 L48 46 L28 74 L48 90 L62 72 L60 154 Q100 159 140 154 L138 72 L152 90 L172 74 L152 46 L132 38 Q116 48 100 48 Q84 48 68 38 Z' fill='url(#main_{$uid})' stroke='#0f172a' stroke-opacity='.25' stroke-width='1.5' stroke-linejoin='round'/>"
                   . "<path d='M72 40 Q100 54 128 40 Q116 49 100 49 Q84 49 72 40 Z' fill='#0f172a' fill-opacity='.45'/>"
                   . "<path d='M68 38 Q100 58 132 38' fill='none' stroke='#93c5fd' stroke-width='2.2' stroke-opacity='.55'/>"
                   . "<path d='M62 72 Q68 95 65 135 M138 72 Q132 95 135 135' stroke='#0f172a' stroke-opacity='.18' stroke-width='2' fill='none' stroke-linecap='round'/>"
                   . "<rect x='76' y='72' width='48' height='26' rx='5' fill='#ffffff' fill-opacity='.95'/>"
                   . "<rect x='79' y='75' width='42' height='20' rx='3' fill='#1e3a8a'/>"
                   . "<text x='100' y='89' text-anchor='middle' fill='#38bdf8' font-family='sans-serif' font-weight='900' font-size='10.5' letter-spacing='1'>F-CTF</text>"
                   . "<line x1='61' y1='149' x2='139' y2='149' stroke='#93c5fd' stroke-opacity='.35' stroke-width='1.2' stroke-dasharray='3 2'/>";
            break;

        case 'polo':
            // Ao Polo co be + nep cai 3 cuc + bo tay
            $inner = $shadow
                   . "<path d='M68 40 L48 48 L28 76 L48 91 L62 74 L60 154 Q100 159 140 154 L138 74 L152 91 L172 76 L152 48 L132 40 Z' fill='url(#main_{$uid})' stroke='#0f172a' stroke-opacity='.25' stroke-width='1.5' stroke-linejoin='round'/>"
                   . "<path d='M31 73 L49 88 M169 73 L151 88' stroke='#bae6fd' stroke-width='3' stroke-opacity='.55'/>"
                   . "<rect x='95' y='52' width='10' height='36' rx='2' fill='#0c4a6e' stroke='#38bdf8' stroke-opacity='.35' stroke-width='1'/>"
                   . "<circle cx='100' cy='60' r='1.8' fill='#f8fafc'/><circle cx='100' cy='69' r='1.8' fill='#f8fafc'/><circle cx='100' cy='78' r='1.8' fill='#f8fafc'/>"
                   . "<path d='M75 36 L100 52 L125 36 L118 33 L100 44 L82 33 Z' fill='#075985'/>"
                   . "<path d='M68 38 L86 58 L100 50 L75 35 Z' fill='#0369a1' stroke='#bae6fd' stroke-opacity='.4' stroke-width='1'/>"
                   . "<path d='M132 38 L114 58 L100 50 L125 35 Z' fill='#0369a1' stroke='#bae6fd' stroke-opacity='.4' stroke-width='1'/>"
                   . "<circle cx='118' cy='76' r='8' fill='#0c4a6e' stroke='#fde047' stroke-width='1.5'/>"
                   . "<text x='118' y='79' text-anchor='middle' fill='#fde047' font-family='monospace' font-weight='900' font-size='7'>&lt;/&gt;</text>";
            break;

        case 'cap':
            // Mu luoi trai Baseball Cap goc nghieng 3/4 that (vom mu 6 mui + luoi trai cong phia truoc)
            $inner = $shadow
                   . "<path d='M42 108 C38 66 66 44 104 46 C134 48 152 70 150 104 C128 112 76 114 42 108 Z' fill='url(#dark_{$uid})'/>"
                   . "<path d='M102 46 C86 62 80 84 80 110 M102 46 C118 62 126 84 126 108 M102 46 C62 62 54 86 52 108' stroke='#475569' stroke-width='1.4' fill='none' opacity='.75'/>"
                   . "<ellipse cx='102' cy='45' rx='5' ry='3' fill='#0f172a' stroke='#64748b' stroke-width='1'/>"
                   . "<circle cx='72' cy='66' r='2' fill='#0f172a' stroke='#94a3b8' stroke-width='.8'/>"
                   . "<circle cx='112' cy='65' r='2' fill='#0f172a' stroke='#94a3b8' stroke-width='.8'/>"
                   . "<rect x='74' y='72' width='36' height='20' rx='4' fill='#0f172a' stroke='#38bdf8' stroke-width='1.5'/>"
                   . "<text x='92' y='86' text-anchor='middle' fill='#38bdf8' font-family='monospace' font-weight='900' font-size='11'>&lt;/&gt;</text>"
                   . "<path d='M38 106 C64 98 110 100 144 104 C166 108 178 122 162 132 C136 142 78 138 44 122 C32 116 32 108 38 106 Z' fill='#0f172a' stroke='#334155' stroke-width='1.5'/>"
                   . "<path d='M46 114 C78 124 126 128 158 122' fill='none' stroke='#475569' stroke-width='1.2' stroke-dasharray='4 3'/>";
            break;

        case 'hoodie':
            // Ao Hoodie ni bong co mu trum 3D, tay dai bo chun va tui Kangaroo
            $inner = $shadow
                   . "<path d='M72 44 C68 22 132 22 128 44 L152 54 L174 128 L154 134 L140 82 L138 152 L62 152 L60 82 L46 134 L26 128 L48 54 Z' fill='url(#dark_{$uid})' stroke='#0f172a' stroke-width='1.5' stroke-linejoin='round'/>"
                   . "<path d='M72 44 C70 24 130 24 128 44 C118 54 82 54 72 44 Z' fill='#1e293b' stroke='#475569' stroke-width='1.5'/>"
                   . "<ellipse cx='100' cy='42' rx='18' ry='9' fill='#0f172a'/>"
                   . "<rect x='62' y='144' width='76' height='9' rx='2' fill='#0f172a'/>"
                   . "<path d='M74 108 L126 108 L132 142 L68 142 Z' fill='#1e293b' stroke='#475569' stroke-width='1.4' stroke-linejoin='round'/>"
                   . "<path d='M74 108 L68 126 M126 108 L132 126' stroke='#64748b' stroke-width='2' stroke-linecap='round'/>"
                   . "<path d='M91 50 Q89 68 91 82 M109 50 Q111 68 109 82' stroke='#e2e8f0' stroke-width='2.5' fill='none' stroke-linecap='round'/>"
                   . "<rect x='89.5' y='80' width='3' height='6' rx='1' fill='#38bdf8'/><rect x='107.5' y='80' width='3' height='6' rx='1' fill='#38bdf8'/>"
                   . "<text x='100' y='92' text-anchor='middle' fill='#38bdf8' font-family='sans-serif' font-weight='900' font-size='11' letter-spacing='1.5'>CYBER</text>";
            break;

        case 'bomber':
            // Ao khoac Bomber phi cong Olive + khoa keo kim loai + tag do tren tay ao
            $inner = $shadow
                   . "<path d='M70 42 L46 52 L26 128 L46 134 L60 82 L62 150 L138 150 L140 82 L154 134 L174 128 L154 52 L130 42 Z' fill='url(#main_{$uid})' stroke='#1a2e05' stroke-width='1.5' stroke-linejoin='round'/>"
                   . "<path d='M72 40 Q100 56 128 40 L124 49 Q100 62 76 49 Z' fill='#1a2e05' stroke='#65a30d' stroke-width='1'/>"
                   . "<rect x='62' y='142' width='76' height='10' rx='3' fill='#1a2e05'/>"
                   . "<line x1='100' y1='54' x2='100' y2='152' stroke='#cbd5e1' stroke-width='2.5'/>"
                   . "<rect x='98' y='62' width='4' height='8' rx='1' fill='#f8fafc'/>"
                   . "<path d='M72 110 L88 118 M128 110 L112 118' stroke='#1a2e05' stroke-width='3.5' stroke-linecap='round'/>"
                   . "<rect x='34' y='74' width='12' height='18' rx='2' transform='rotate(-12 40 83)' fill='#273818' stroke='#1a2e05' stroke-width='1'/>"
                   . "<path d='M34 82 L26 106 L33 108 L40 84 Z' fill='#dc2626'/>"
                   . "<circle cx='118' cy='76' r='9' fill='#1a2e05' stroke='#eab308' stroke-width='1.5'/>"
                   . "<polygon points='118,70 120,74 124,75 121,78 122,82 118,80 114,82 115,78 112,75 116,74' fill='#eab308'/>";
            break;

        case 'bottle':
            // Binh giu nhiet Inox 304 co anh kim + nap van + quai xach
            $inner = $shadow
                   . "<defs><linearGradient id='flask_{$uid}' x1='0' y1='0' x2='1' y2='0'>"
                   . "<stop offset='0%' stop-color='#0f766e'/><stop offset='28%' stop-color='#2dd4bf'/>"
                   . "<stop offset='55%' stop-color='#0d9488'/><stop offset='85%' stop-color='#115e59'/><stop offset='100%' stop-color='#134e4a'/>"
                   . "</linearGradient></defs>"
                   . "<path d='M110 28 C126 28 128 42 112 42' fill='none' stroke='#334155' stroke-width='3.5' stroke-linecap='round'/>"
                   . "<rect x='84' y='24' width='32' height='16' rx='4' fill='url(#dark_{$uid})'/>"
                   . "<rect x='83' y='40' width='34' height='6' fill='url(#metal_{$uid})'/>"
                   . "<path d='M83 46 L117 46 L126 66 L126 148 A10 10 0 0 1 116 158 L84 158 A10 10 0 0 1 74 148 L74 66 Z' fill='url(#flask_{$uid})'/>"
                   . "<path d='M74 66 H126 V73 H74 Z' fill='url(#metal_{$uid})'/>"
                   . "<rect x='82' y='50' width='5' height='102' rx='2.5' fill='#ffffff' fill-opacity='.32'/>"
                   . "<text x='100' y='112' text-anchor='middle' fill='#ffffff' font-family='sans-serif' font-weight='800' font-size='10' letter-spacing='1'>INOX 304</text>"
                   . "<text x='100' y='125' text-anchor='middle' fill='#ccfbf1' font-family='sans-serif' font-weight='700' font-size='8'>500 ML</text>";
            break;

        case 'bag':
            // Balo Laptop 15.6" vai Oxford chong soc 3D
            $inner = $shadow
                   . "<path d='M84 36 C84 22 116 22 116 36' fill='none' stroke='#0f172a' stroke-width='5' stroke-linecap='round'/>"
                   . "<path d='M52 94 L44 138 Q44 146 54 146 M148 94 L156 138 Q156 146 146 146' fill='#1e293b' stroke='#0f172a' stroke-width='2'/>"
                   . "<path d='M62 40 H138 A18 18 0 0 1 154 58 V144 A14 14 0 0 1 140 158 H60 A14 14 0 0 1 46 144 V58 A18 18 0 0 1 62 40 Z' fill='url(#dark_{$uid})' stroke='#0f172a' stroke-width='1.5'/>"
                   . "<path d='M56 58 Q100 50 144 58' fill='none' stroke='#64748b' stroke-width='2'/>"
                   . "<rect x='60' y='88' width='80' height='56' rx='10' fill='#1e293b' stroke='#475569' stroke-width='1.5'/>"
                   . "<line x1='64' y1='102' x2='136' y2='102' stroke='#94a3b8' stroke-width='2'/>"
                   . "<rect x='96' y='100' width='8' height='10' rx='2' fill='#38bdf8'/>"
                   . "<rect x='84' y='64' width='32' height='12' rx='3' fill='#0f172a' stroke='#38bdf8' stroke-width='1'/>"
                   . "<text x='100' y='73' text-anchor='middle' fill='#38bdf8' font-family='sans-serif' font-weight='800' font-size='7.5'>F-CTF</text>";
            break;

        case 'sling':
            // Tui deo cheo Tactical Crossbody Cordura
            $inner = $shadow
                   . "<path d='M48 42 L84 96 M148 134 L164 156' stroke='#0f172a' stroke-width='12' stroke-linecap='round'/>"
                   . "<rect x='56' y='56' width='14' height='18' rx='3' transform='rotate(-34 63 65)' fill='#334155' stroke='#f97316' stroke-width='1.5'/>"
                   . "<g transform='rotate(-14 104 110)'>"
                   . "<rect x='50' y='76' width='108' height='66' rx='16' fill='url(#dark_{$uid})' stroke='#0f172a' stroke-width='1.8'/>"
                   . "<path d='M56 94 H152' stroke='#38bdf8' stroke-width='2' stroke-dasharray='3 2'/>"
                   . "<rect x='114' y='92' width='12' height='6' rx='2' fill='#f97316'/>"
                   . "<rect x='62' y='112' width='84' height='10' rx='2' fill='#0f172a'/>"
                   . "<line x1='78' y1='112' x2='78' y2='122' stroke='#475569' stroke-width='2'/>"
                   . "<line x1='96' y1='112' x2='96' y2='122' stroke='#475569' stroke-width='2'/>"
                   . "<line x1='114' y1='112' x2='114' y2='122' stroke='#475569' stroke-width='2'/>"
                   . "<line x1='132' y1='112' x2='132' y2='122' stroke='#475569' stroke-width='2'/>"
                   . "</g>";
            break;

        case 'keyboard':
            // Ban phim co Mini 68 phim khung nhom den, keycap PBT 2 tông mau + vien LED RGB
            $keys = '';
            $rows = [
                ['y' => 68, 'w' => [11,10,10,10,10,10,10,10,10,10,14], 'accent' => [0 => '#f97316', 10 => '#475569']],
                ['y' => 82, 'w' => [14,10,10,10,10,10,10,10,10,10,11], 'accent' => [0 => '#475569']],
                ['y' => 96, 'w' => [16,10,10,10,10,10,10,10,10,19],    'accent' => [0 => '#475569', 9 => '#f97316']],
                ['y' => 110,'w' => [20,10,10,10,10,10,10,10,10,15],    'accent' => [0 => '#475569', 8 => '#38bdf8']],
                ['y' => 124,'w' => [13,13,13,44,11,11,10,10],          'accent' => [3 => '#f97316', 5 => '#38bdf8', 6 => '#38bdf8', 7 => '#38bdf8']],
            ];
            foreach ($rows as $r) {
                $x = 30;
                foreach ($r['w'] as $idx => $w) {
                    $fill = $r['accent'][$idx] ?? '#e2e8f0';
                    $keys .= "<rect x='{$x}' y='{$r['y']}' width='{$w}' height='10.5' rx='2' fill='{$fill}' stroke='#0f172a' stroke-width='.8'/>";
                    $x += $w + 2.2;
                }
            }
            $inner = $shadow
                   . "<path d='M100 56 V42 Q100 34 112 34 H148' fill='none' stroke='#f97316' stroke-width='3.5' stroke-linecap='round'/>"
                   . "<rect x='20' y='58' width='160' height='88' rx='10' fill='#020617'/>"
                   . "<rect x='22' y='56' width='156' height='86' rx='9' fill='url(#dark_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<rect x='26' y='140' width='148' height='3' rx='1.5' fill='#38bdf8'/>"
                   . $keys;
            break;

        case 'mouse':
            // Chuot gaming khong day cong thai hoc den nham + con lan kim loai + LED RGB
            $inner = $shadow
                   . "<path d='M100 34 C72 34 62 58 64 96 C65 132 76 154 100 154 C124 154 135 132 136 96 C138 58 128 34 100 34 Z' fill='url(#dark_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<path d='M65 82 Q100 90 135 82' fill='none' stroke='#0f172a' stroke-width='2.2'/>"
                   . "<line x1='100' y1='35' x2='100' y2='85' stroke='#0f172a' stroke-width='2.2'/>"
                   . "<rect x='95' y='46' width='10' height='24' rx='5' fill='url(#metal_{$uid})' stroke='#0f172a' stroke-width='1.5'/>"
                   . "<rect x='97' y='74' width='6' height='7' rx='2' fill='#22c55e'/>"
                   . "<path d='M86 128 Q100 116 114 128 Q100 138 86 128 Z' fill='none' stroke='#22c55e' stroke-width='2.2'/>"
                   . "<path d='M63 88 Q56 102 64 120' fill='none' stroke='#22c55e' stroke-width='2' opacity='.8'/>";
            break;

        case 'headset':
            // Tai nghe chup tai Gaming 7.1 dem da + vong LED + micro khu on
            $inner = $shadow
                   . "<path d='M48 98 C48 42 152 42 152 98' fill='none' stroke='#1e293b' stroke-width='12' stroke-linecap='round'/>"
                   . "<path d='M56 90 C56 48 144 48 144 90' fill='none' stroke='#6366f1' stroke-width='4' stroke-linecap='round'/>"
                   . "<g transform='rotate(-8 50 112)'>"
                   . "<rect x='34' y='86' width='26' height='50' rx='13' fill='url(#dark_{$uid})' stroke='#6366f1' stroke-width='2'/>"
                   . "<rect x='52' y='88' width='12' height='46' rx='6' fill='#0f172a'/>"
                   . "</g>"
                   . "<g transform='rotate(8 150 112)'>"
                   . "<rect x='140' y='86' width='26' height='50' rx='13' fill='url(#dark_{$uid})' stroke='#6366f1' stroke-width='2'/>"
                   . "<rect x='136' y='88' width='12' height='46' rx='6' fill='#0f172a'/>"
                   . "</g>"
                   . "<path d='M52 130 Q62 154 98 150' fill='none' stroke='#334155' stroke-width='3.5' stroke-linecap='round'/>"
                   . "<ellipse cx='102' cy='150' rx='7' ry='4.5' fill='#0f172a' stroke='#38bdf8' stroke-width='1.5'/>";
            break;

        case 'yubikey':
            // Khoa bao mat cung FIDO2 USB-C den nham + chan dong vang + vong cam ung vang
            $inner = $shadow
                   . "<g transform='rotate(-12 100 96)'>"
                   . "<rect x='132' y='82' width='30' height='26' rx='4' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<rect x='138' y='87' width='18' height='4' rx='1' fill='#eab308'/>"
                   . "<rect x='138' y='99' width='18' height='4' rx='1' fill='#eab308'/>"
                   . "<rect x='36' y='70' width='100' height='50' rx='10' fill='url(#dark_{$uid})' stroke='#334155' stroke-width='1.8'/>"
                   . "<circle cx='52' cy='95' r='7' fill='{$bg2}' stroke='#64748b' stroke-width='2'/>"
                   . "<circle cx='96' cy='95' r='15' fill='#eab308' fill-opacity='.2' stroke='#eab308' stroke-width='2.2'/>"
                   . "<circle cx='96' cy='95' r='9' fill='#eab308'/>"
                   . "<text x='96' y='99' text-anchor='middle' fill='#0f172a' font-family='sans-serif' font-weight='900' font-size='10'>Y</text>"
                   . "</g>"
                   . "<text x='100' y='148' text-anchor='middle' fill='#a16207' font-family='sans-serif' font-weight='800' font-size='10' letter-spacing='1.5'>FIDO2 / NFC</text>";
            break;

        case 'usb':
            // USB Dual OTG 128GB vo kim loai xoay 360 do
            $inner = $shadow
                   . "<g transform='rotate(-14 100 96)'>"
                   . "<rect x='26' y='83' width='28' height='24' rx='2' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<rect x='32' y='88' width='6' height='5' fill='#0f172a'/><rect x='32' y='97' width='6' height='5' fill='#0f172a'/>"
                   . "<rect x='146' y='86' width='22' height='18' rx='5' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<rect x='52' y='76' width='96' height='38' rx='8' fill='url(#dark_{$uid})'/>"
                   . "<path d='M68 72 H136 A8 8 0 0 1 144 80 V110 A8 8 0 0 1 136 118 H68 Z' fill='url(#metal_{$uid})' stroke='#64748b' stroke-width='1.5'/>"
                   . "<circle cx='78' cy='95' r='6' fill='#0f172a' stroke='#38bdf8' stroke-width='2'/>"
                   . "<text x='112' y='94' text-anchor='middle' fill='#0f172a' font-family='sans-serif' font-weight='900' font-size='11'>128GB</text>"
                   . "<text x='112' y='105' text-anchor='middle' fill='#334155' font-family='sans-serif' font-weight='700' font-size='7.5'>USB 3.2 OTG</text>"
                   . "</g>";
            break;

        case 'hub':
            // Hub chuyen doi Type-C 7-in-1 vo nhom Space Grey
            $inner = $shadow
                   . "<path d='M44 98 H24 Q16 98 16 84 V66' fill='none' stroke='#1e293b' stroke-width='5' stroke-linecap='round'/>"
                   . "<rect x='10' y='50' width='12' height='18' rx='3' fill='url(#metal_{$uid})' stroke='#334155' stroke-width='1.5'/>"
                   . "<rect x='42' y='72' width='128' height='52' rx='10' fill='url(#dark_{$uid})' stroke='#64748b' stroke-width='1.5'/>"
                   . "<rect x='54' y='84' width='18' height='8' rx='1.5' fill='#020617' stroke='#38bdf8' stroke-width='1'/>"
                   . "<rect x='80' y='84' width='18' height='8' rx='1.5' fill='#020617' stroke='#38bdf8' stroke-width='1'/>"
                   . "<rect x='106' y='84' width='18' height='8' rx='1.5' fill='#020617' stroke='#38bdf8' stroke-width='1'/>"
                   . "<rect x='134' y='84' width='22' height='8' rx='2' fill='#020617' stroke='#94a3b8' stroke-width='1'/>"
                   . "<rect x='54' y='104' width='28' height='4' rx='1' fill='#020617'/>"
                   . "<rect x='90' y='104' width='18' height='4' rx='1' fill='#020617'/>"
                   . "<circle cx='152' cy='106' r='3' fill='#22c55e'/>"
                   . "<text x='106' y='144' text-anchor='middle' fill='#334155' font-family='sans-serif' font-weight='800' font-size='10'>7-IN-1 TYPE-C HUB</text>";
            break;

        case 'stand':
            // Gia do Laptop Nhom CNC kem Laptop mo nghieng
            $inner = $shadow
                   . "<path d='M52 148 L76 106 L124 106 L148 148 Z' fill='none' stroke='url(#metal_{$uid})' stroke-width='6' stroke-linejoin='round'/>"
                   . "<path d='M44 148 H156' stroke='#64748b' stroke-width='5' stroke-linecap='round'/>"
                   . "<rect x='48' y='34' width='104' height='66' rx='6' fill='#0f172a' stroke='#475569' stroke-width='2'/>"
                   . "<rect x='53' y='39' width='94' height='56' rx='3' fill='#1e293b'/>"
                   . "<rect x='60' y='47' width='36' height='4' rx='2' fill='#38bdf8'/>"
                   . "<rect x='60' y='56' width='62' height='4' rx='2' fill='#22c55e'/>"
                   . "<rect x='60' y='65' width='48' height='4' rx='2' fill='#f59e0b'/>"
                   . "<rect x='60' y='74' width='70' height='4' rx='2' fill='#94a3b8'/>"
                   . "<path d='M38 100 L162 100 L150 110 L50 110 Z' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.5'/>";
            break;

        case 'mousepad':
            // Lot chuot World Map XXL + chuot tren ban
            $inner = $shadow
                   . "<rect x='18' y='52' width='164' height='92' rx='12' fill='#0f172a' stroke='#10b981' stroke-width='2'/>"
                   . "<path d='M34 76 C46 68 62 70 68 78 C70 86 60 94 54 100 C48 108 44 120 38 114 C32 102 28 84 34 76 Z M84 72 C98 66 128 68 142 78 C148 88 136 102 124 104 C112 106 104 98 94 102 C88 112 82 120 76 112 C72 98 76 78 84 72 Z M132 114 C142 110 154 114 152 124 C148 130 134 128 132 114 Z' fill='#1e293b' stroke='#334155' stroke-width='1'/>"
                   . "<rect x='144' y='84' width='20' height='32' rx='10' fill='#1e293b' stroke='#10b981' stroke-width='1.5'/>"
                   . "<line x1='154' y1='85' x2='154' y2='96' stroke='#10b981' stroke-width='1.5'/>";
            break;

        case 'sticker':
            // Bo Sticker die-cut vien trang do bong
            $inner = $shadow
                   . "<g transform='rotate(-14 66 86)'>"
                   . "<rect x='34' y='54' width='64' height='64' rx='14' fill='#ffffff' stroke='#cbd5e1' stroke-width='1.5'/>"
                   . "<rect x='39' y='59' width='54' height='54' rx='10' fill='#0f172a'/>"
                   . "<text x='66' y='92' text-anchor='middle' fill='#22c55e' font-family='monospace' font-weight='900' font-size='19'>&gt;_</text>"
                   . "</g>"
                   . "<g transform='rotate(12 132 84)'>"
                   . "<circle cx='132' cy='84' r='34' fill='#ffffff' stroke='#cbd5e1' stroke-width='1.5'/>"
                   . "<circle cx='132' cy='84' r='29' fill='#2563eb'/>"
                   . "<text x='132' y='90' text-anchor='middle' fill='#ffffff' font-family='monospace' font-weight='900' font-size='16'>&lt;/&gt;</text>"
                   . "</g>"
                   . "<g transform='rotate(-4 100 128)'>"
                   . "<rect x='62' y='104' width='76' height='44' rx='12' fill='#ffffff' stroke='#cbd5e1' stroke-width='1.5'/>"
                   . "<rect x='66' y='108' width='68' height='36' rx='9' fill='#ea580c'/>"
                   . "<text x='100' y='131' text-anchor='middle' fill='#ffffff' font-family='sans-serif' font-weight='900' font-size='13'>F-CTF</text>"
                   . "</g>";
            break;

        case 'book':
        case 'notebook':
        case 'pybook':
            // Sach / So tay bia cung 3D co gay sach va ru-bang danh dau trang
            $title = ($icon === 'book') ? 'WEB SEC' : (($icon === 'pybook') ? 'PYTHON' : 'PENTEST');
            $sub   = ($icon === 'book') ? 'CTF HANDBOOK' : (($icon === 'pybook') ? 'FOR PENTESTER' : 'CHECKLIST A5');
            $cover = ($icon === 'book') ? '#1e3a8a' : (($icon === 'pybook') ? '#1d4ed8' : '#1f2937');
            $accent= ($icon === 'book') ? '#38bdf8' : (($icon === 'pybook') ? '#facc15' : '#f59e0b');
            $inner = $shadow
                   . "<path d='M62 34 L142 34 A6 6 0 0 1 148 40 L148 148 A6 6 0 0 1 142 154 L62 154 Z' fill='#f8fafc' stroke='#94a3b8' stroke-width='1.5'/>"
                   . "<path d='M54 38 L136 38 A6 6 0 0 1 142 44 L142 152 A6 6 0 0 1 136 158 L54 158 Z' fill='{$cover}'/>"
                   . "<rect x='52' y='38' width='14' height='120' rx='3' fill='#0f172a'/>"
                   . "<path d='M92 158 V170 L98 165 L104 170 V158 Z' fill='#ef4444'/>"
                   . "<rect x='74' y='56' width='56' height='36' rx='5' fill='#0f172a' stroke='{$accent}' stroke-width='1.5'/>"
                   . "<text x='102' y='74' text-anchor='middle' fill='{$accent}' font-family='sans-serif' font-weight='900' font-size='10.5'>{$title}</text>"
                   . "<text x='102' y='85' text-anchor='middle' fill='#e2e8f0' font-family='sans-serif' font-weight='700' font-size='6.5'>{$sub}</text>"
                   . "<line x1='76' y1='112' x2='128' y2='112' stroke='{$accent}' stroke-width='2' opacity='.7'/>"
                   . "<line x1='76' y1='122' x2='116' y2='122' stroke='#cbd5e1' stroke-width='2' opacity='.4'/>";
            break;

        case 'mug':
            // Coc su trang men den doi mau ma nhi phan Matrix + khoi bốc lên
            $inner = $shadow
                   . "<path d='M122 76 H140 A16 16 0 0 1 156 92 V112 A16 16 0 0 1 140 128 H122' fill='none' stroke='#0f172a' stroke-width='8' stroke-linecap='round'/>"
                   . "<path d='M52 60 H126 V132 A20 20 0 0 1 106 152 H72 A20 20 0 0 1 52 132 Z' fill='url(#dark_{$uid})'/>"
                   . "<ellipse cx='89' cy='60' rx='37' ry='8' fill='#334155' stroke='#0f172a' stroke-width='1.5'/>"
                   . "<ellipse cx='89' cy='61' rx='32' ry='5.5' fill='#78350f'/>"
                   . "<text x='89' y='98' text-anchor='middle' fill='#22c55e' font-family='monospace' font-weight='900' font-size='14'>010110</text>"
                   . "<text x='89' y='116' text-anchor='middle' fill='#4ade80' font-family='monospace' font-weight='900' font-size='14'>101001</text>"
                   . "<path d='M76 46 Q70 36 78 26 M98 46 Q92 36 100 26' fill='none' stroke='#94a3b8' stroke-width='2.5' stroke-linecap='round' opacity='.65'/>";
            break;

        case 'webcam':
            // Webcam Full HD 1080p ong kinh thuy tinh phan quang + chan kep man hinh
            $inner = $shadow
                   . "<path d='M68 138 L56 154 H144 L132 138 Z' fill='#1e293b' stroke='#0f172a' stroke-width='1.5'/>"
                   . "<rect x='88' y='118' width='24' height='22' rx='4' fill='#334155'/>"
                   . "<rect x='34' y='58' width='132' height='62' rx='18' fill='url(#dark_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<circle cx='100' cy='89' r='23' fill='url(#metal_{$uid})'/>"
                   . "<circle cx='100' cy='89' r='18' fill='#020617'/>"
                   . "<circle cx='100' cy='89' r='11' fill='#0c4a6e'/>"
                   . "<circle cx='100' cy='89' r='5' fill='#020617'/>"
                   . "<circle cx='95' cy='84' r='3' fill='#bae6fd' fill-opacity='.85'/>"
                   . "<circle cx='148' cy='72' r='3' fill='#22c55e'/>"
                   . "<rect x='46' y='82' width='16' height='14' rx='3' fill='#0f172a'/><rect x='138' y='82' width='16' height='14' rx='3' fill='#0f172a'/>";
            break;

        case 'powerbank':
            // Pin du phong 20.000mAh den nham + man hinh tron LED % pin
            $inner = $shadow
                   . "<rect x='58' y='30' width='84' height='128' rx='16' fill='url(#dark_{$uid})' stroke='#334155' stroke-width='1.8'/>"
                   . "<circle cx='100' cy='68' r='22' fill='#020617' stroke='#10b981' stroke-width='2'/>"
                   . "<text x='100' y='73' text-anchor='middle' fill='#10b981' font-family='sans-serif' font-weight='900' font-size='14'>100%</text>"
                   . "<path d='M102 100 L94 114 H101 L98 126 L108 110 H101 Z' fill='#10b981'/>"
                   . "<text x='100' y='144' text-anchor='middle' fill='#94a3b8' font-family='sans-serif' font-weight='800' font-size='8.5'>20000mAh PD 22.5W</text>";
            break;

        case 'charger':
            // Cu sac nhanh GaN 65W trang su + chan cam kim loai + 2 cong USB-C/A
            $inner = $shadow
                   . "<rect x='82' y='28' width='8' height='24' rx='2' fill='url(#metal_{$uid})' stroke='#64748b' stroke-width='1.2'/>"
                   . "<rect x='110' y='28' width='8' height='24' rx='2' fill='url(#metal_{$uid})' stroke='#64748b' stroke-width='1.2'/>"
                   . "<rect x='60' y='50' width='80' height='102' rx='16' fill='#ffffff' stroke='#cbd5e1' stroke-width='2'/>"
                   . "<rect x='68' y='58' width='64' height='86' rx='12' fill='#f8fafc'/>"
                   . "<text x='100' y='92' text-anchor='middle' fill='#0f172a' font-family='sans-serif' font-weight='900' font-size='20'>65W</text>"
                   . "<text x='100' y='105' text-anchor='middle' fill='#f59e0b' font-family='sans-serif' font-weight='800' font-size='9'>GaN FAST</text>"
                   . "<rect x='88' y='118' width='24' height='7' rx='3.5' fill='#0f172a'/>"
                   . "<rect x='84' y='130' width='32' height='8' rx='2' fill='#0f172a'/><rect x='87' y='132' width='26' height='3' fill='#f97316'/>";
            break;

        case 'cable':
            // Cap sac Type-C to Type-C 100W boc du cuon tron + 2 dau hop kim
            $inner = $shadow
                   . "<ellipse cx='100' cy='104' rx='46' ry='36' fill='none' stroke='#1e293b' stroke-width='9'/>"
                   . "<ellipse cx='100' cy='104' rx='46' ry='36' fill='none' stroke='#f97316' stroke-width='3' stroke-dasharray='4 4'/>"
                   . "<rect x='54' y='44' width='20' height='28' rx='4' fill='url(#dark_{$uid})' stroke='#475569' stroke-width='1.2'/>"
                   . "<rect x='58' y='34' width='12' height='10' rx='2' fill='url(#metal_{$uid})'/>"
                   . "<rect x='126' y='44' width='20' height='28' rx='4' fill='url(#dark_{$uid})' stroke='#475569' stroke-width='1.2'/>"
                   . "<rect x='130' y='34' width='12' height='10' rx='2' fill='url(#metal_{$uid})'/>"
                   . "<rect x='78' y='94' width='44' height='20' rx='6' fill='#0f172a' stroke='#f97316' stroke-width='1.5'/>"
                   . "<text x='100' y='108' text-anchor='middle' fill='#f97316' font-family='sans-serif' font-weight='900' font-size='11'>100W</text>";
            break;

        case 'speaker':
            // Loa Bluetooth mini chong nuoc IPX6 luoi vai + màng loa bass
            $inner = $shadow
                   . "<path d='M138 66 C158 66 158 96 138 96' fill='none' stroke='#06b6d4' stroke-width='4' stroke-linecap='round'/>"
                   . "<rect x='54' y='42' width='92' height='112' rx='26' fill='url(#dark_{$uid})' stroke='#334155' stroke-width='2'/>"
                   . "<circle cx='100' cy='86' r='26' fill='#0f172a' stroke='#06b6d4' stroke-width='2'/>"
                   . "<circle cx='100' cy='86' r='12' fill='#1e293b' stroke='#38bdf8' stroke-width='1.5'/>"
                   . "<text x='100' y='136' text-anchor='middle' fill='#e2e8f0' font-family='sans-serif' font-weight='900' font-size='12'>＋  ▶  －</text>";
            break;

        case 'earbuds':
            // Tai nghe True Wireless ANC kem hop sac mo nap
            $inner = $shadow
                   . "<path d='M52 86 Q52 54 100 54 Q148 54 148 86 Z' fill='#ffffff' stroke='#cbd5e1' stroke-width='2'/>"
                   . "<rect x='52' y='92' width='96' height='62' rx='24' fill='#ffffff' stroke='#cbd5e1' stroke-width='2'/>"
                   . "<line x1='54' y1='106' x2='146' y2='106' stroke='#e2e8f0' stroke-width='2'/>"
                   . "<circle cx='100' cy='128' r='3.5' fill='#22c55e'/>"
                   . "<g transform='translate(0,2)'><circle cx='78' cy='78' r='11' fill='#0f172a'/><rect x='75' y='84' width='6' height='18' rx='3' fill='#0f172a'/><circle cx='78' cy='78' r='4' fill='#38bdf8'/></g>"
                   . "<g transform='translate(0,2)'><circle cx='122' cy='78' r='11' fill='#0f172a'/><rect x='119' y='84' width='6' height='18' rx='3' fill='#0f172a'/><circle cx='122' cy='78' r='4' fill='#38bdf8'/></g>";
            break;

        case 'lamp':
            // Den ban LED chong loa 3 che do anh sang
            $inner = $shadow
                   . "<polygon points='92,56 164,74 182,154 74,154' fill='#fef08a' fill-opacity='.45'/>"
                   . "<ellipse cx='74' cy='152' rx='34' ry='8' fill='url(#dark_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<path d='M66 150 L52 88 L104 46' fill='none' stroke='url(#dark_{$uid})' stroke-width='7' stroke-linecap='round' stroke-linejoin='round'/>"
                   . "<circle cx='52' cy='88' r='5' fill='#38bdf8'/>"
                   . "<path d='M96 42 L158 64 L154 72 L92 50 Z' fill='url(#dark_{$uid})'/>"
                   . "<path d='M98 49 L152 69' stroke='#fef08a' stroke-width='4' stroke-linecap='round'/>";
            break;

        case 'board':
            // Kit Raspberry Pi 4 (4GB) bo mach xanh la + chip Broadcom + cong USB 3.0 xanh + GPIO
            $pins = '';
            for ($i = 0; $i < 14; $i++) {
                $px = 42 + $i * 6;
                $pins .= "<rect x='{$px}' y='52' width='3' height='8' fill='#facc15'/>";
            }
            $inner = $shadow
                   . "<rect x='30' y='46' width='140' height='98' rx='8' fill='#15803d' stroke='#14532d' stroke-width='2'/>"
                   . $pins
                   . "<rect x='64' y='76' width='36' height='36' rx='3' fill='url(#metal_{$uid})' stroke='#334155' stroke-width='1.5'/>"
                   . "<text x='82' y='98' text-anchor='middle' fill='#0f172a' font-family='sans-serif' font-weight='900' font-size='9'>Pi 4B</text>"
                   . "<rect x='110' y='82' width='20' height='24' rx='2' fill='#0f172a'/>"
                   . "<rect x='146' y='62' width='28' height='22' rx='2' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.2'/>"
                   . "<rect x='146' y='88' width='28' height='20' rx='2' fill='url(#metal_{$uid})' stroke='#0284c7' stroke-width='1.5'/>"
                   . "<rect x='146' y='112' width='28' height='22' rx='2' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.2'/>"
                   . "<rect x='42' y='136' width='16' height='10' rx='2' fill='url(#metal_{$uid})'/>"
                   . "<rect x='66' y='136' width='14' height='10' rx='2' fill='url(#metal_{$uid})'/>"
                   . "<rect x='86' y='136' width='14' height='10' rx='2' fill='url(#metal_{$uid})'/>";
            break;

        case 'monitor':
            // Man hinh 24 inch Full HD IPS vien mong + giao dien code Dark Mode
            $inner = $shadow
                   . "<path d='M92 124 L86 150 H114 L108 124 Z' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.5'/>"
                   . "<path d='M64 152 L100 146 L136 152' fill='none' stroke='#334155' stroke-width='5' stroke-linecap='round'/>"
                   . "<rect x='24' y='34' width='152' height='92' rx='6' fill='#0f172a' stroke='#334155' stroke-width='2'/>"
                   . "<rect x='28' y='38' width='144' height='80' rx='3' fill='#020617'/>"
                   . "<rect x='28' y='38' width='28' height='80' fill='#0f172a'/>"
                   . "<rect x='64' y='48' width='48' height='5' rx='2' fill='#38bdf8'/>"
                   . "<rect x='64' y='58' width='84' height='4' rx='2' fill='#22c55e'/>"
                   . "<rect x='72' y='67' width='68' height='4' rx='2' fill='#f59e0b'/>"
                   . "<rect x='72' y='76' width='52' height='4' rx='2' fill='#a855f7'/>"
                   . "<rect x='64' y='85' width='40' height='4' rx='2' fill='#38bdf8'/>"
                   . "<rect x='118' y='82' width='44' height='28' rx='4' fill='#1e293b' stroke='#38bdf8' stroke-width='1'/>";
            break;

        case 'lanyard':
            // Day deo the Lanyard F-CTF + moc khoa kim loai + the STAFF co ma vach
            $inner = $shadow
                   . "<path d='M62 20 L96 88 L104 88 L138 20 L126 20 L100 74 L74 20 Z' fill='#1e3a8a'/>"
                   . "<path d='M95 88 L105 88 L103 102 L97 102 Z' fill='url(#metal_{$uid})' stroke='#475569' stroke-width='1.2'/>"
                   . "<rect x='66' y='100' width='68' height='62' rx='7' fill='#ffffff' stroke='#94a3b8' stroke-width='1.8'/>"
                   . "<rect x='66' y='100' width='68' height='16' rx='6' fill='#dc2626'/>"
                   . "<rect x='90' y='105' width='20' height='4' rx='2' fill='#ffffff'/>"
                   . "<circle cx='84' cy='132' r='9' fill='#dbeafe' stroke='#1e3a8a' stroke-width='1.5'/>"
                   . "<rect x='98' y='124' width='26' height='5' rx='2' fill='#1e293b'/>"
                   . "<rect x='98' y='133' width='18' height='4' rx='2' fill='#ef4444'/>"
                   . "<text x='100' y='154' text-anchor='middle' fill='#0f172a' font-family='monospace' font-weight='900' font-size='9'>||| || ||| ||</text>";
            break;

        case 'socks':
            // Set tat cotton Binary Code co phan got va mui tat phoi mau
            $inner = $shadow
                   . "<g transform='translate(-14, -4) rotate(-8 90 100)'>"
                   . "<path d='M72 38 H108 V104 Q108 118 122 126 L142 136 Q154 142 146 152 Q138 160 118 152 L84 138 Q72 132 72 114 Z' fill='#334155'/>"
                   . "</g>"
                   . "<path d='M78 36 H116 V104 Q116 118 132 126 L152 136 Q164 142 156 154 Q146 162 124 154 L90 140 Q78 134 78 114 Z' fill='#0f172a'/>"
                   . "<rect x='78' y='36' width='38' height='14' rx='2' fill='#14b8a6'/>"
                   . "<path d='M78 112 Q94 112 96 128 L90 140 Q78 134 78 114 Z' fill='#14b8a6'/>"
                   . "<path d='M140 130 L152 136 Q164 142 156 154 Q148 160 134 156 Z' fill='#14b8a6'/>"
                   . "<text x='97' y='74' text-anchor='middle' fill='#2dd4bf' font-family='monospace' font-weight='900' font-size='11'>0101</text>"
                   . "<text x='97' y='90' text-anchor='middle' fill='#5eead4' font-family='monospace' font-weight='900' font-size='11'>1010</text>";
            break;

        default:
            $inner = $shadow
                   . "<polygon points='100,44 114,76 148,80 122,104 129,138 100,120 71,138 78,104 52,80 86,76' fill='url(#main_{$uid})'/>";
            break;
    }

    return '<svg viewBox="0 0 200 185" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="' . htmlspecialchars($icon) . '">'
         . '<defs>' . $defs . '</defs>'
         . '<rect width="200" height="185" rx="14" fill="url(#bg_' . $uid . ')"/>'
         . '<circle cx="168" cy="28" r="34" fill="#ffffff" fill-opacity="0.5"/>'
         . '<circle cx="28" cy="154" r="26" fill="#ffffff" fill-opacity="0.4"/>'
         . $inner
         . '</svg>';
}

function render_product_thumb($p, $large = false) {
    $h = $large ? '290px' : '175px';
    $html = '<div class="product-thumb" style="height:' . $h . '">';
    if (!empty($p['badge'])) {
        $html .= '<span class="product-badge">' . htmlspecialchars($p['badge']) . '</span>';
    }
    if (!empty($p['old_price']) && (int)$p['old_price'] > (int)$p['price']) {
        $pct = round((1 - (int)$p['price'] / (int)$p['old_price']) * 100);
        if ($pct > 0) {
            $html .= '<span class="discount-badge">-' . (int)$pct . '%</span>';
        }
    }
    // Thu tu uu tien hinh: 1) image_url trong DB  2) file anh rieng images/products/<id>.(jpg|jpeg|png|webp)
    // 3) hinh minh hoa SVG theo icon (luon dung voi san pham, khong phu thuoc mang ngoai).
    $photo = !empty($p['image_url']) ? $p['image_url'] : '';
    if ($photo === '' && !empty($p['id'])) {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $rel = 'images/products/' . (int)$p['id'] . '.' . $ext;
            if (is_file(__DIR__ . '/' . $rel)) { $photo = base_prefix() . $rel; break; }
        }
    }
    if ($photo !== '') {
        $safePhoto = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8');
        $html .= '<img class="product-img" loading="lazy" src="' . $safePhoto . '" alt="' . $safeName . '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'block\'">';
        $html .= '<div class="product-fallback" style="display:none">' . product_svg($p['icon'] ?? 'shirt') . '</div>';
    } else {
        $html .= product_svg($p['icon'] ?? 'shirt');
    }
    $html .= '</div>';
    return $html;
}

function status_badge($status) {
    $map = [
        'Chờ xử lý'  => 'badge-warn',
        'Đang giao'  => 'badge-info',
        'Hoàn thành' => 'badge-ok',
        'Đã hủy'     => 'badge-err',
    ];
    $cls = $map[$status] ?? 'badge-neutral';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($status) . '</span>';
}

function nav() {
    $b = base_prefix();
    $cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $inAdmin = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false);
    $count = cart_count();

    echo '<header class="site-header"><div class="container nav-wrap">';
    echo '<a class="brand" href="' . $b . 'index.php">';
    echo '<span class="brand-logo">🛍️</span>';
    echo '<span>Shop<strong>Lite</strong></span>';
    echo '<span class="brand-tag">F-CTF Store</span>';
    echo '</a>';

    echo '<nav class="nav-links">';
    echo '<a class="' . (!$inAdmin && $cur === 'index.php' ? 'active' : '') . '" href="' . $b . 'index.php">Trang chủ</a>';
    echo '<a class="cart-link ' . ($cur === 'cart.php' || $cur === 'checkout.php' ? 'active' : '') . '" href="' . $b . 'cart.php">Giỏ hàng <span class="cart-pill">' . (int)$count . '</span></a>';
    echo '<a class="' . ($cur === 'wishlist.php' ? 'active' : '') . '" href="' . $b . 'wishlist.php">♡ Yêu thích <span class="cart-pill wishlist-pill">' . (int)wishlist_count() . '</span></a>';
    echo '<a class="' . ($cur === 'track_order.php' ? 'active' : '') . '" href="' . $b . 'track_order.php">📦 Tra cứu đơn hàng</a>';
    echo '<button type="button" class="theme-toggle" id="themeToggle" aria-label="Đổi giao diện sáng tối" title="Chuyển chế độ sáng / tối"><span class="theme-icon" aria-hidden="true">🌙</span><span class="theme-label">Giao diện</span></button>'; 
    echo '<button type="button" class="theme-toggle fx-toggle" id="fxToggle" aria-pressed="true" aria-label="Bật hoặc tắt hiệu ứng chuột" title="Bật / tắt hiệu ứng ngôi sao theo chuột"><span class="theme-icon" aria-hidden="true">✨</span><span class="theme-label">Hiệu ứng: <span class="fx-state">Bật</span></span></button>';

    if (!empty($_SESSION['uid'])) {
        echo '<a class="' . ($cur === 'profile.php' ? 'active' : '') . '" href="' . $b . 'profile.php">Hồ sơ & Đơn hàng</a>';
        if (($_SESSION['role'] ?? '') === 'admin') {
            echo '<a class="admin-link ' . ($inAdmin ? 'active' : '') . '" href="' . $b . 'admin/index.php">⚡ Quản trị</a>';
        }
        echo '<span class="nav-divider"></span>';
        $navAvatar = '';
        try {
            $avatarStmt = db()->prepare('SELECT avatar FROM users WHERE id = ?');
            $avatarStmt->execute([$_SESSION['uid']]);
            $navAvatar = (string)($avatarStmt->fetchColumn() ?: '');
        } catch (Throwable $e) { $navAvatar = ''; }
        if ($navAvatar !== '') {
            echo '<a class="nav-user" href="' . $b . 'profile.php" title="Mở hồ sơ"><img class="nav-avatar" src="' . $b . 'uploads/' . rawurlencode(basename($navAvatar)) . '" alt="Avatar"></a>';
        } else {
            echo '<a class="nav-user nav-avatar-fallback" href="' . $b . 'profile.php" title="Thêm ảnh đại diện">' . htmlspecialchars(strtoupper(substr((string)$_SESSION['username'], 0, 1))) . '</a>';
        }
        echo '<a class="user-chip" href="' . $b . 'profile.php"><span class="user-dot"></span>' . htmlspecialchars($_SESSION['username']) . '</a>';
        echo '<a class="logout-link" href="' . $b . 'logout.php">Đăng xuất</a>';
    } else {
        echo '<span class="nav-divider"></span>';
        echo '<a class="' . ($cur === 'login.php' ? 'active' : '') . '" href="' . $b . 'login.php">Đăng nhập</a>';
        echo '<a class="btn btn-sm" href="' . $b . 'register.php">Đăng ký</a>';
    }
    echo '</nav></div></header>';
}

function layout_head($title) {
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . '</title><style>';
    echo <<<'CSS'
:root {
  --bg: rgba(255,255,255,.58);
  --surface: rgba(255,255,255,.78);
  --surface-alt: rgba(248,250,252,.62);
  --text: #0f172a;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --primary: #2563eb;
  --primary-hover: #1d4ed8;
  --primary-soft: #eff6ff;
  --accent: #0284c7;
  --success: #16a34a;
  --success-bg: #f0fdf4;
  --success-border: #bbf7d0;
  --danger: #dc2626;
  --danger-bg: #fef2f2;
  --danger-border: #fecaca;
  --warn: #d97706;
  --warn-bg: #fffbeb;
  --radius: 14px;
  --shadow-sm: 0 1px 3px rgba(15,23,42,0.06), 0 1px 2px rgba(15,23,42,0.04);
  --shadow-md: 0 10px 25px -5px rgba(15,23,42,0.08), 0 4px 10px -3px rgba(15,23,42,0.04);
}
* { box-sizing: border-box; }
body {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: radial-gradient(ellipse at 12% 8%, rgba(147,197,253,.36), transparent 34%), radial-gradient(ellipse at 88% 18%, rgba(125,211,252,.32), transparent 32%), radial-gradient(ellipse at 60% 88%, rgba(249,168,212,.25), transparent 38%), linear-gradient(135deg, #f8fbff 0%, #fdf8ff 48%, #f7fcff 100%);
  background-attachment: fixed;
  color: var(--text);
  margin: 0;
  padding: 0;
  line-height: 1.55;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.container {
  max-width: 1120px;
  margin: 0 auto;
  padding: 0 20px;
  width: 100%;
}
main.page-main {
  flex: 1;
  padding: 28px 0 56px;
}
/* Soft translucent outer canvas and glass surfaces */
.card, .product-card, .review-card, .order-card, .filter-card, .stat-card, .form-card {
  background-color: rgba(255,255,255,.70);
  -webkit-backdrop-filter: blur(14px);
  backdrop-filter: blur(14px);
  border-color: rgba(255,255,255,.78);
  box-shadow: 0 12px 32px rgba(80,75,140,.08), inset 0 1px 0 rgba(255,255,255,.8);
}
.chat-launcher { display: grid !important; place-items: center; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; }
body.dark-mode .card, body.dark-mode .product-card, body.dark-mode .review-card, body.dark-mode .order-card, body.dark-mode .filter-card, body.dark-mode .stat-card, body.dark-mode .form-card { background-color: rgba(17,28,48,.78); border-color: rgba(71,85,105,.55); }
.chat-panel { z-index: 12000 !important; }
.chat-launcher { z-index: 12001 !important; }
.cursor-spark { z-index: 11999 !important; }
/* Header & Nav */
.site-header {
  background: rgba(255, 255, 255, 0.68);
  -webkit-backdrop-filter: blur(16px);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 2px rgba(15,23,42,0.03);
}
.nav-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 68px;
  gap: 16px;
  flex-wrap: wrap;
}
.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 1.35rem;
  color: var(--text);
  text-decoration: none;
  letter-spacing: -0.02em;
}
.brand strong { color: var(--primary); font-weight: 800; }
.brand-logo {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb, #0284c7);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  box-shadow: 0 4px 10px rgba(37,99,235,0.25);
}
.brand-tag {
  font-size: 0.72rem;
  font-weight: 600;
  background: var(--primary-soft);
  color: var(--primary);
  padding: 3px 8px;
  border-radius: 999px;
  border: 1px solid #bfdbfe;
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.nav-links a {
  color: #334155;
  text-decoration: none;
  font-size: 0.94rem;
  font-weight: 500;
  padding: 8px 13px;
  border-radius: 10px;
  transition: all 0.15s ease;
}
.nav-links a:hover {
  background: var(--surface-alt);
  color: var(--primary);
}
.nav-links a.active {
  background: var(--primary-soft);
  color: var(--primary);
  font-weight: 600;
}
.nav-links a.admin-link {
  color: #0284c7;
  background: #eef2ff;
}
.nav-links a.admin-link:hover {
  background: #e0e7ff;
}
.nav-links a.logout-link {
  color: #dc2626;
}
.nav-links a.logout-link:hover {
  background: #fef2f2;
}
.cart-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--primary);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 999px;
  margin-left: 4px;
}
.nav-divider {
  width: 1px;
  height: 22px;
  background: var(--border);
  margin: 0 4px;
}
.user-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--text);
  background: var(--surface-alt);
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid var(--border);
}
.user-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
}

/* Typography & Layout */
h1, h2, h3, h4 {
  color: var(--text);
  margin-top: 0;
  letter-spacing: -0.015em;
}
h1 { font-size: 1.75rem; font-weight: 800; margin-bottom: 18px; }
h2 { font-size: 1.3rem; font-weight: 700; margin-bottom: 14px; }
h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
p { margin: 8px 0; }
.muted { color: var(--text-muted); font-size: 0.9rem; }

/* Cards & Grids */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  padding: 22px;
  border-radius: var(--radius);
  margin: 16px 0;
  box-shadow: var(--shadow-sm);
}
.card-hover {
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.card-hover:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: #cbd5e1;
}
.grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}
.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.grid-3 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}
.grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
@media (max-width: 980px) {
  .grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .grid, .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
}

/* Hero Banner */
.hero {
  background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 55%, #ffffff 100%);
  border: 1px solid #dbeafe;
  border-radius: 18px;
  padding: 26px 30px;
  margin-bottom: 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}
.hero h1 {
  font-size: 1.75rem;
  margin-bottom: 8px;
  color: #1e3a8a;
}
.hero p {
  color: #475569;
  font-size: 0.97rem;
  max-width: 640px;
  margin-bottom: 14px;
}
.voucher-bar {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.voucher-pill {
  background: #ffffff;
  border: 1px dashed #3b82f6;
  color: #1d4ed8;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(37,99,235,0.06);
}

/* Product Cards & Thumbnails */
.product-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;
  margin: 0;
  padding: 16px;
  position: relative;
}
.product-thumb {
  width: 100%;
  height: 175px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
  position: relative;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}
.product-thumb svg {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.28s ease;
}
.product-thumb img.product-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.28s ease;
}
.product-card:hover .product-thumb svg,
.product-card:hover .product-thumb img.product-img {
  transform: scale(1.06);
}
.product-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  z-index: 2;
  background: #2563eb;
  color: #ffffff;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 999px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  box-shadow: 0 2px 6px rgba(37,99,235,0.28);
}
.discount-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 2;
  background: #dc2626;
  color: #ffffff;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 999px;
}
.product-cat {
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 4px;
}
.product-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text);
  text-decoration: none;
  display: block;
  margin-bottom: 6px;
  line-height: 1.35;
}
.product-title:hover { color: var(--primary); }
.price-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin: 10px 0 12px;
}
.price-current {
  font-size: 1.12rem;
  font-weight: 800;
  color: #dc2626;
}
.price-old {
  font-size: 0.82rem;
  color: #94a3b8;
  text-decoration: line-through;
}

/* Pagination */
.pagination-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 28px;
  padding: 16px 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
}
.pagination {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.page-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 38px;
  height: 38px;
  padding: 0 12px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: #ffffff;
  color: #334155;
  font-size: 0.92rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s ease;
}
.page-link:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-soft);
}
.page-link.active {
  background: var(--primary);
  color: #ffffff;
  border-color: var(--primary);
  box-shadow: 0 3px 8px rgba(37,99,235,0.25);
}
.page-link.disabled {
  opacity: 0.45;
  pointer-events: none;
  background: var(--surface-alt);
}

/* Category Quick Tabs */
.cat-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.cat-tab {
  display: inline-flex;
  align-items: center;
  padding: 7px 15px;
  border-radius: 999px;
  font-size: 0.86rem;
  font-weight: 600;
  text-decoration: none;
  background: #ffffff;
  color: #475569;
  border: 1px solid var(--border);
  transition: all 0.15s ease;
}
.cat-tab:hover {
  border-color: var(--primary);
  color: var(--primary);
}
.cat-tab.active {
  background: var(--primary);
  color: #ffffff;
  border-color: var(--primary);
}

/* Forms & Inputs */
label {
  display: block;
  font-size: 0.88rem;
  font-weight: 600;
  color: #334155;
  margin: 10px 0 4px;
}
input, textarea, select {
  width: 100%;
  padding: 10px 13px;
  margin: 4px 0 10px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: var(--text);
  font-size: 0.94rem;
  font-family: inherit;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
}
button, a.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: var(--primary);
  color: #ffffff !important;
  border: 1px solid transparent;
  padding: 10px 18px;
  border-radius: 10px;
  font-size: 0.92rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: background 0.15s ease;
}
button:hover, a.btn:hover {
  background: var(--primary-hover);
  text-decoration: none;
}
.btn-secondary {
  background: #ffffff !important;
  color: #334155 !important;
  border: 1px solid #cbd5e1 !important;
}
.btn-secondary:hover {
  background: #f1f5f9 !important;
  color: var(--text) !important;
}
.btn-danger {
  background: #fee2e2 !important;
  color: #dc2626 !important;
  border: 1px solid #fecaca !important;
}
.btn-danger:hover {
  background: #fecaca !important;
}
.btn-sm {
  padding: 7px 12px !important;
  font-size: 0.84rem !important;
  border-radius: 8px !important;
}
.btn-block { width: 100%; }

/* Alerts & CTF Flag */
.ok {
  background: var(--success-bg);
  border: 1px solid var(--success-border);
  color: #15803d;
  padding: 12px 16px;
  border-radius: 10px;
  font-weight: 500;
  margin: 12px 0;
}
.err {
  background: var(--danger-bg);
  border: 1px solid var(--danger-border);
  color: var(--danger);
  padding: 12px 16px;
  border-radius: 10px;
  font-weight: 500;
  margin: 12px 0;
}
.flag {
  background: #ecfdf5;
  border: 1.5px solid #10b981;
  color: #065f46;
  padding: 14px 18px;
  border-radius: 10px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-weight: 700;
  word-break: break-all;
  margin: 12px 0;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 600;
}
.badge-ok { background: #dcfce7; color: #15803d; }
.badge-warn { background: #fef3c7; color: #b45309; }
.badge-info { background: #dbeafe; color: #1d4ed8; }
.badge-err { background: #fee2e2; color: #b91c1c; }
.badge-neutral { background: #f1f5f9; color: #475569; }

/* Tables */
.table-wrap { overflow-x: auto; }
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.93rem;
}
th {
  background: var(--surface-alt);
  color: #475569;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  padding: 12px 14px;
  text-align: left;
  border-bottom: 1px solid var(--border);
}
td {
  padding: 12px 14px;
  text-align: left;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
tr:last-child td { border-bottom: none; }

/* Avatar & KPI */
img.avatar {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--primary);
  background: #f1f5f9;
  box-shadow: var(--shadow-sm);
}
.avatar-placeholder {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: linear-gradient(135deg, #dbeafe, #e0e7ff);
  color: var(--primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 2.2rem;
  font-weight: 800;
  border: 3px solid #bfdbfe;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 18px 20px;
  box-shadow: var(--shadow-sm);
}
.stat-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text);
  margin-top: 4px;
}

/* Filter Bar */
.filter-bar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 18px;
  margin-bottom: 16px;
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
  box-shadow: var(--shadow-sm);
}
.filter-bar input, .filter-bar select {
  margin: 0;
  width: auto;
  flex: 1;
  min-width: 160px;
}

/* Footer */
.site-footer {
  background: #ffffff;
  border-top: 1px solid var(--border);
  padding: 24px 0;
  color: var(--text-muted);
  font-size: 0.88rem;
  margin-top: auto;
}
.footer-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

/* ShopLite polish: smooth motion, theme switch, focus states, responsive nav */
html { scroll-behavior: smooth; }
body { transition: background-color .25s ease, color .25s ease; }
.site-header { transition: background-color .25s ease, border-color .25s ease; }
.nav-links a, .btn, button, .cat-tab, .page-link { transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease; }
.btn:hover, button:hover { transform: translateY(-1px); }
.btn:active, button:active { transform: translateY(0) scale(.98); }
:focus-visible { outline: 3px solid rgba(59,130,246,.5); outline-offset: 2px; }
.theme-toggle { display:inline-flex; align-items:center; justify-content:center; gap:7px; border: 1px solid var(--border); background: var(--surface); color: var(--text); border-radius: 12px; min-height:38px; padding:7px 11px; cursor:pointer; font-size:1rem; box-shadow:0 2px 8px rgba(15,23,42,.04); }
.theme-toggle .theme-icon { font-size:1.15rem; line-height:1; }
.theme-toggle .theme-label { font-size:.82rem; font-weight:650; }
.product-img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .45s cubic-bezier(.2,.7,.2,1), filter .3s ease; }
.product-thumb { overflow:hidden; }
.product-thumb:hover .product-img { transform:scale(1.045); }
.product-fallback, .product-fallback svg { width:100%; height:100%; }
.theme-toggle:hover { box-shadow: var(--shadow-sm); }
.wishlist-pill { background: #e11d48; }
.order-items-list { display:flex; flex-direction:column; gap:10px; min-width:220px; }
.order-item-row { display:flex; align-items:center; gap:10px; min-width:220px; }
.order-item-thumb { flex:0 0 58px; width:58px; height:58px; overflow:hidden; border:1px solid var(--border); border-radius:10px; background:var(--surface-alt); display:flex; align-items:center; justify-content:center; }
.order-item-thumb .product-thumb { width:58px; height:58px !important; min-height:58px; border-radius:9px; border:0; }
.order-item-thumb .product-badge, .order-item-thumb .discount-badge { display:none; }
.order-item-info { display:flex; flex-direction:column; gap:3px; min-width:0; }
.order-item-info strong { font-size:.9rem; line-height:1.3; }
.product-card { animation: cardIn .45s both; }
.product-card:nth-child(2) { animation-delay: .04s; }
.product-card:nth-child(3) { animation-delay: .08s; }
.product-card:nth-child(4) { animation-delay: .12s; }
.product-card:nth-child(n+5) { animation-delay: .16s; }
@keyframes cardIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.hero { position: relative; overflow: hidden; box-shadow: 0 14px 40px rgba(37,99,235,.08); }
.hero:after { content: ''; position: absolute; width: 240px; height: 240px; right: -80px; top: -110px; border-radius: 50%; background: rgba(14,165,233,.10); pointer-events: none; }
.product-card:hover { transform: translateY(-5px); }
.product-thumb { background: linear-gradient(145deg, #f8fafc, #eef2ff); }
.wish-btn { position: absolute; z-index: 3; top: 46px; right: 10px; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(226,232,240,.9); background: rgba(255,255,255,.94); color: #64748b; text-decoration: none; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(15,23,42,.10); }
.wish-btn:hover, .wish-btn.is-saved { color: #e11d48; background: #fff1f2; }
.toast { position: fixed; z-index: 999; right: 22px; bottom: 22px; padding: 13px 18px; border-radius: 12px; color: #fff; background: #0f172a; box-shadow: 0 12px 35px rgba(15,23,42,.22); opacity: 0; transform: translateY(12px); pointer-events: none; transition: all .25s ease; }
.toast.show { opacity: 1; transform: translateY(0); }
.back-top { position: fixed; right: 22px; bottom: 78px; width: 42px; height: 42px; border: 0; border-radius: 50%; background: var(--primary); color: white; box-shadow: var(--shadow-md); cursor: pointer; opacity: 0; pointer-events: none; transform: translateY(8px); transition: all .2s ease; z-index: 90; }
.back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
body.dark-mode { --bg: #0b1120; --surface: rgba(17,28,48,.86); --surface: #111c30; --surface-alt: #18263d; --text: #e5edf8; --text-muted: #9eacc2; --border: #263750; --primary: #60a5fa; --primary-hover: #93c5fd; --primary-soft: #172d4d; --success-bg: #102d25; --success-border: #1f624c; --danger-bg: #351c26; --danger-border: #713044; --warn-bg: #332817; }
body.dark-mode .site-header, body.dark-mode .site-footer { background: rgba(17,28,48,.96); }
body.dark-mode .nav-links a, body.dark-mode label, body.dark-mode .hero p, body.dark-mode .product-title { color: var(--text); }
body.dark-mode .hero { background: linear-gradient(135deg, #142846, #1d2145 60%, #111c30); border-color: #2c4266; }
body.dark-mode .hero h1 { color: #dbeafe; }
body.dark-mode input, body.dark-mode textarea, body.dark-mode select { background: #0d1728; color: var(--text); border-color: #344761; }
body.dark-mode .cat-tab, body.dark-mode .voucher-pill { background: var(--surface); color: var(--text); border-color: var(--border); }
body.dark-mode .cat-tab.active, body.dark-mode .cat-tab:hover { background:linear-gradient(110deg,#2563eb,#0ea5e9); color:#fff; border-color:transparent; }
body.dark-mode .hero:before { background:rgba(56,189,248,.10); }
body.dark-mode .product-thumb { background: #17243a; border-color: var(--border); }
body.dark-mode .wish-btn { background: #17243a; border-color: #344761; }
@media (max-width: 760px) { .nav-wrap { padding-top: 10px; padding-bottom: 10px; } .nav-links { gap: 4px; } .nav-links a { padding: 7px 9px; font-size: .85rem; } .brand-tag { display: none; } .hero { padding: 22px; } .toast { left: 14px; right: 14px; bottom: 14px; text-align: center; } }
/* ShopLite v4: avatar navigation and floating customer-support assistant */
.nav-user { display:inline-flex !important; align-items:center; justify-content:center; padding:0 !important; border-radius:50%; text-decoration:none; flex:0 0 auto; }
.nav-avatar { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--primary); box-shadow:0 3px 10px rgba(37,99,235,.18); }
.nav-avatar-fallback { width:34px; height:34px; background:linear-gradient(135deg,#60a5fa,#60a5fa); color:#fff !important; font-weight:800; border:2px solid rgba(255,255,255,.9); }
.user-chip { text-decoration:none; }
.chat-launcher { position:fixed; z-index:1001; right:22px; bottom:22px; width:58px; height:58px; border:0; border-radius:20px; color:#fff; background:linear-gradient(135deg,#2563eb,#2563eb); box-shadow:0 12px 30px rgba(37,99,235,.35); font-size:1.55rem; cursor:pointer; transition:transform .2s, box-shadow .2s; }
.chat-launcher:hover { transform:translateY(-3px) rotate(-3deg); box-shadow:0 16px 34px rgba(37,99,235,.42); }
.chat-launcher .chat-online { position:absolute; width:12px; height:12px; right:5px; top:5px; border-radius:50%; background:#22c55e; border:2px solid #fff; }
.chat-panel { position:fixed; z-index:1000; right:22px; bottom:92px; width:min(370px,calc(100vw - 28px)); height:min(520px,calc(100vh - 130px)); display:none; flex-direction:column; overflow:hidden; border:1px solid var(--border); border-radius:22px; background:var(--surface); color:var(--text); box-shadow:0 24px 70px rgba(15,23,42,.24); transform-origin:bottom right; }
.chat-panel.open { display:flex; animation:chatPop .22s ease-out; }
@keyframes chatPop { from { opacity:0; transform:translateY(10px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.chat-head { padding:16px; display:flex; align-items:center; gap:11px; color:white; background:linear-gradient(120deg,#1d4ed8,#2563eb); }
.chat-bot-avatar { width:42px; height:42px; border-radius:15px; display:grid; place-items:center; background:rgba(255,255,255,.18); font-size:1.45rem; }
.chat-head-info { flex:1; min-width:0; } .chat-head-info strong { display:block; } .chat-head-info small { opacity:.86; }
.chat-close { border:0; background:rgba(255,255,255,.15); color:white; border-radius:10px; width:34px; height:34px; cursor:pointer; font-size:1.2rem; }
.chat-messages { flex:1; min-height:0; overflow-y:auto; padding:15px; display:flex; flex-direction:column; gap:10px; background:var(--bg); }
.chat-message { max-width:88%; padding:10px 12px; border-radius:15px; line-height:1.45; font-size:.91rem; white-space:pre-wrap; overflow-wrap:anywhere; }
.chat-message.bot { align-self:flex-start; background:var(--surface); border:1px solid var(--border); border-bottom-left-radius:5px; }
.chat-message.user { align-self:flex-end; color:#fff; background:linear-gradient(135deg,#2563eb,#0284c7); border-bottom-right-radius:5px; }
.chat-quick { display:flex; flex-wrap:wrap; gap:7px; padding:0 13px 11px; background:var(--surface); }
.chat-quick button { border:1px solid var(--border); border-radius:999px; background:var(--surface); color:var(--text); padding:7px 10px; font-size:.78rem; cursor:pointer; }
.chat-quick button:hover { border-color:var(--primary); color:var(--primary); }
.chat-form { display:flex; gap:8px; padding:12px; border-top:1px solid var(--border); background:var(--surface); }
.chat-form input { min-width:0; flex:1; margin:0; border-radius:13px; }
.chat-form button { border:0; border-radius:13px; min-width:44px; background:var(--primary); color:white; cursor:pointer; font-size:1.1rem; }
.chat-note { padding:0 13px 9px; color:var(--text-muted); font-size:.7rem; background:var(--surface); }
body.dark-mode .chat-messages { background:#0b1120; } body.dark-mode .chat-message.bot { background:#17243a; }
@media (max-width:760px) { .chat-panel { right:14px; bottom:88px; width:calc(100vw - 28px); height:min(500px,calc(100vh - 115px)); } .chat-launcher { right:16px; bottom:16px; width:54px; height:54px; border-radius:18px; } .nav-avatar { width:30px; height:30px; } }
@media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; } }

/* ShopLite v6: colorful gradients, micro-interactions and animated page entrance */
:root {
  --primary: #1d4ed8;
  --primary-hover: #1e40af;
  --primary-soft: #e0f2fe;
  --accent: #0ea5e9;
  --radius: 18px;
  --shadow-sm: 0 5px 18px rgba(30,64,175,.07);
  --shadow-md: 0 18px 42px rgba(30,64,175,.14);
}
body {
  background:
    radial-gradient(ellipse at 8% 4%, rgba(125,211,252,.24), transparent 28%),
    radial-gradient(ellipse at 92% 18%, rgba(125,211,252,.20), transparent 26%),
    var(--bg);
}
.site-header {
  background: color-mix(in srgb, var(--surface) 88%, transparent);
  border-bottom-color: color-mix(in srgb, #60a5fa 28%, var(--border));
  box-shadow: 0 8px 28px rgba(30,64,175,.07);
}
.brand-logo {
  background: linear-gradient(135deg,#2563eb 0%,#38bdf8 52%,#06b6d4 100%);
  box-shadow: 0 8px 22px rgba(37,99,235,.30);
  animation: logoFloat 4s ease-in-out infinite;
}
.brand strong { background: linear-gradient(100deg,#2563eb,#0ea5e9,#06b6d4); -webkit-background-clip:text; background-clip:text; color:transparent; }
.brand-tag { color:#1d4ed8; background:linear-gradient(120deg,#e0f2fe,#dbeafe); border-color:#bae6fd; }
.nav-links a { border:1px solid transparent; }
.nav-links a:hover { background:linear-gradient(120deg,#e0f2fe,#dbeafe); color:#1d4ed8; transform:translateY(-2px); }
.nav-links a.active { background:linear-gradient(120deg,#dbeafe,#e0f2fe); color:#1d4ed8; border-color:#bae6fd; }
.nav-links a.admin-link { background:linear-gradient(120deg,#e0f2fe,#dbeafe); color:#1d4ed8; }
.hero {
  isolation:isolate;
  background:linear-gradient(118deg,#e0f2fe 0%,#dbeafe 48%,#eff6ff 100%);
  border:1px solid rgba(147,197,253,.8);
  border-radius:26px;
  padding:clamp(24px,4vw,42px);
  box-shadow:0 20px 55px rgba(37,99,235,.13);
  animation:heroArrive .65s cubic-bezier(.2,.75,.25,1) both;
}
.hero:before { content:''; position:absolute; z-index:-1; width:230px; height:230px; border-radius:50%; right:12%; bottom:-145px; background:rgba(255,255,255,.48); filter:blur(1px); animation:orbDrift 8s ease-in-out infinite; }
.hero:after { width:180px; height:180px; right:-44px; top:-75px; background:linear-gradient(135deg,rgba(56,189,248,.30),rgba(96,165,250,.24)); animation:orbDrift 7s ease-in-out infinite reverse; }
.hero h1 { color:#1e3a8a; }
.hero p { color:#51466e; }
.card, .product-card {
  border-radius:20px;
  border-color:color-mix(in srgb,#93c5fd 38%,var(--border));
  box-shadow:0 7px 24px rgba(30,64,175,.06);
  transition:transform .24s cubic-bezier(.2,.75,.25,1),box-shadow .24s ease,border-color .24s ease;
}
.card-hover:hover, .product-card:hover { transform:translateY(-7px); box-shadow:0 20px 42px rgba(30,64,175,.15); border-color:#93c5fd; }
.product-card { overflow:hidden; }
.product-thumb { border-radius:16px 16px 0 0; background:linear-gradient(135deg,#e0f2fe,#dbeafe,#eff6ff); }
.product-img { transition:transform .55s cubic-bezier(.2,.7,.2,1),filter .35s ease; }
.product-card:hover .product-img { transform:scale(1.075) rotate(-1deg); filter:saturate(1.12); }
.product-badge { background:linear-gradient(120deg,#2563eb,#0ea5e9) !important; box-shadow:0 5px 14px rgba(37,99,235,.25); }
.discount-badge { background:linear-gradient(120deg,#f97316,#e11d48) !important; box-shadow:0 5px 14px rgba(225,29,72,.20); }
.btn, a.btn, button.btn {
  border:0;
  border-radius:12px;
  background:linear-gradient(115deg,#2563eb,#0ea5e9 55%,#38bdf8);
  color:#fff;
  box-shadow:0 7px 18px rgba(37,99,235,.20);
  font-weight:700;
  background-size:160% 160%;
  transition:transform .2s ease,box-shadow .2s ease,background-position .35s ease;
}
.btn:hover, a.btn:hover, button.btn:hover { color:#fff; background-position:100% 50%; box-shadow:0 12px 25px rgba(37,99,235,.30); transform:translateY(-2px); }
button:not(.theme-toggle):not(.wish-btn):not(.chat-close):not(.chat-launcher):not(.back-top):not(.chat-quick button), input[type=submit] { transition:filter .2s ease,transform .2s ease,box-shadow .2s ease; }
input:not([type=checkbox]):not([type=radio]), textarea, select { border-radius:12px; transition:border-color .2s ease,box-shadow .2s ease,background-color .2s ease; }
input:focus, textarea:focus, select:focus { border-color:#60a5fa !important; box-shadow:0 0 0 4px rgba(96,165,250,.19) !important; outline:none; }
.voucher-pill { border-radius:999px; background:linear-gradient(120deg,#fff,#f0f9ff); border-color:#93c5fd; color:#1d4ed8; }
.cat-tab { border-radius:999px; border-color:#bfdbfe; }
.cat-tab:hover, .cat-tab.active { background:linear-gradient(110deg,#2563eb,#0ea5e9); border-color:transparent; color:#fff; box-shadow:0 6px 16px rgba(37,99,235,.22); }
.cart-pill { background:linear-gradient(120deg,#2563eb,#0ea5e9); }
.wishlist-pill { background:linear-gradient(120deg,#fb7185,#e11d48); }
.user-chip { border-color:#bfdbfe; background:linear-gradient(120deg,#eff6ff,#dbeafe); color:#1d4ed8; }
.site-footer { background:linear-gradient(110deg,#f0f9ff,#eff6ff,#eff6ff); border-top-color:#bae6fd; }
.chat-launcher { background:linear-gradient(135deg,#2563eb,#0ea5e9,#06b6d4); background-size:180% 180%; animation:chatGlow 5s ease infinite, chatPulse 2.8s ease-in-out infinite; }
.chat-head { background:linear-gradient(115deg,#1d4ed8,#0ea5e9,#2563eb); background-size:180% 180%; animation:chatGlow 8s ease infinite; }
.toast { background:linear-gradient(110deg,#1d4ed8,#0ea5e9); }
.back-top { background:linear-gradient(135deg,#2563eb,#38bdf8); }
.ui-reveal { opacity:0; transform:translateY(14px) scale(.99); transition:opacity .48s ease,transform .48s cubic-bezier(.2,.75,.25,1); transition-delay:var(--reveal-delay,0ms); }
.ui-reveal.ui-revealed { opacity:1; transform:translateY(0) scale(1); }
/* Gentle entrance reveal for major content blocks */
.page-main > .container > .hero, .page-main > .container > .card { animation:contentRise .5s ease both; }
.page-main > .container > .card:nth-of-type(2) { animation-delay:.06s; }
.page-main > .container > .card:nth-of-type(3) { animation-delay:.12s; }
@keyframes logoFloat { 0%,100% { transform:translateY(0) rotate(0); } 50% { transform:translateY(-2px) rotate(-3deg); } }
@keyframes heroArrive { from { opacity:0; transform:translateY(12px) scale(.99); } to { opacity:1; transform:translateY(0) scale(1); } }
@keyframes contentRise { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
@keyframes orbDrift { 0%,100% { transform:translate3d(0,0,0); } 50% { transform:translate3d(-12px,12px,0); } }
@keyframes chatGlow { 0%,100% { background-position:0% 50%; } 50% { background-position:100% 50%; } }
@keyframes chatPulse { 0%,100% { box-shadow:0 12px 30px rgba(37,99,235,.30); } 50% { box-shadow:0 12px 36px rgba(14,165,233,.42); } }
body.dark-mode { --primary:#60a5fa; --primary-hover:#93c5fd; --primary-soft:#12304f; }
body.dark-mode { background:radial-gradient(ellipse at 8% 4%,rgba(37,99,235,.14),transparent 28%),radial-gradient(ellipse at 92% 18%,rgba(14,165,233,.10),transparent 26%),var(--bg); }
body.dark-mode .hero { background:linear-gradient(118deg,#12274a,#0f3350 48%,#172b4a); border-color:#2c5282; }
body.dark-mode .hero h1 { color:#e0f2fe; }
body.dark-mode .hero p { color:#c3d7ee; }
body.dark-mode .nav-links a:hover, body.dark-mode .nav-links a.active { background:linear-gradient(120deg,#12304f,#0f3a5c); color:#bae6fd; border-color:#2c5282; }
body.dark-mode .brand-tag, body.dark-mode .user-chip { background:linear-gradient(120deg,#12304f,#0f3a5c); color:#bae6fd; border-color:#2c5282; }
body.dark-mode .site-footer { background:linear-gradient(110deg,#111827,#13263f,#17243a); }
body.dark-mode .voucher-pill { background:linear-gradient(120deg,#13263f,#17243a); color:#bae6fd; }
@media (max-width:760px) { .hero { border-radius:20px; } .product-card:hover,.card-hover:hover { transform:translateY(-3px); } }
@media (prefers-reduced-motion: reduce) { .brand-logo,.hero,.hero:before,.hero:after,.chat-launcher,.chat-head,.product-card,.page-main > .container > .card { animation:none !important; } }


/* V9: pin the chatbot to the lower-right viewport edge on every page */
#shopChatLauncher.chat-launcher { position:fixed !important; left:auto !important; right:22px !important; bottom:22px !important; margin:0 !important; transform-origin:center; }
#shopChatPanel.chat-panel { position:fixed !important; left:auto !important; right:22px !important; bottom:92px !important; }
@media (max-width:760px) {
  #shopChatLauncher.chat-launcher { right:16px !important; bottom:16px !important; }
  #shopChatPanel.chat-panel { right:14px !important; bottom:82px !important; width:calc(100vw - 28px) !important; }
}

/* V7: clean white glass UI + playful cursor sparkles */
body:not(.dark-mode) {
  background:
    radial-gradient(ellipse 70% 55% at 10% 6%, rgba(186,230,253,.55), transparent 62%),
    radial-gradient(ellipse 60% 50% at 92% 14%, rgba(221,214,254,.45), transparent 60%),
    radial-gradient(ellipse 65% 55% at 78% 96%, rgba(251,207,232,.34), transparent 62%),
    radial-gradient(ellipse 55% 45% at 6% 88%, rgba(167,243,208,.26), transparent 60%),
    linear-gradient(160deg, #f4f9ff 0%, #f6f4ff 52%, #fbf6fb 100%);
  background-attachment: fixed;
}
body:not(.dark-mode) .site-header { background:rgba(255,255,255,.52); border-bottom:1px solid rgba(255,255,255,.7); box-shadow:0 6px 24px rgba(14,165,233,.07); backdrop-filter:blur(18px) saturate(1.5); -webkit-backdrop-filter:blur(18px) saturate(1.5); }
body:not(.dark-mode) .site-footer { background:rgba(255,255,255,.45); border-top-color:rgba(255,255,255,.7); backdrop-filter:blur(16px) saturate(1.4); -webkit-backdrop-filter:blur(16px) saturate(1.4); }
body:not(.dark-mode) .card, body:not(.dark-mode) .product-card, body:not(.dark-mode) .filter-bar, body:not(.dark-mode) .pagination-wrap { background:rgba(255,255,255,.55); backdrop-filter:blur(16px) saturate(1.5); -webkit-backdrop-filter:blur(16px) saturate(1.5); }
body:not(.dark-mode) .card, body:not(.dark-mode) .product-card, body:not(.dark-mode) .pagination-wrap { border-color:rgba(255,255,255,.75); box-shadow:0 10px 30px rgba(14,165,233,.08), inset 0 1px 0 rgba(255,255,255,.85); }
body:not(.dark-mode) .hero { background:linear-gradient(120deg,rgba(255,255,255,.62),rgba(224,242,254,.50),rgba(243,232,255,.50)); backdrop-filter:blur(14px) saturate(1.4); -webkit-backdrop-filter:blur(14px) saturate(1.4); border-color:rgba(255,255,255,.75); }
body:not(.dark-mode) input, body:not(.dark-mode) textarea, body:not(.dark-mode) select { background:rgba(255,255,255,.78); }
body:not(.dark-mode) .cat-tab:not(.active):not(:hover), body:not(.dark-mode) .page-link:not(.active):not(:hover) { background:rgba(255,255,255,.7); }
.btn, .nav-links a, .cat-tab, .wish-btn, .chat-launcher { position:relative; }
.btn:hover, .nav-links a:hover, .cat-tab:hover { filter:saturate(1.15); }
.btn:hover { box-shadow:0 8px 22px rgba(14,165,233,.2); }
.cursor-spark { position:fixed; z-index:2000; pointer-events:none; user-select:none; will-change:transform,opacity; filter:drop-shadow(0 0 2px #fff) drop-shadow(0 0 7px #0ea5e9) drop-shadow(0 0 16px #0369a1); animation:starFall .85s cubic-bezier(.16,1,.3,1) forwards; }
.cursor-spark svg { display:block; width:100%; height:100%; }
.cursor-speck { position:fixed; z-index:1999; width:4px; height:4px; border-radius:50%; pointer-events:none; background:#fff; box-shadow:0 0 6px 2px #38bdf8, 0 0 14px 4px #0284c7; animation:speckFall .7s ease-out forwards; }
@keyframes starFall {
  0%   { opacity:1;   transform:translate(-50%,-50%) scale(1.05) rotate(var(--rot,0deg)); }
  45%  { opacity:.9;  transform:translate(calc(-50% + var(--dx,0px) * .5), calc(-50% + var(--dy,30px) * .55)) scale(.72) rotate(calc(var(--rot,0deg) + 18deg)); }
  100% { opacity:0;   transform:translate(calc(-50% + var(--dx,0px)), calc(-50% + var(--dy,30px))) scale(.15) rotate(calc(var(--rot,0deg) + 40deg)); }
}
@keyframes speckFall { 0% { opacity:.95; transform:translate(-50%,-50%) scale(1); } 100% { opacity:0; transform:translate(calc(-50% + var(--dx,0px)), calc(-50% + var(--dy,24px))) scale(.2); } }
@media (pointer:coarse) { .cursor-spark, .cursor-speck { display:none !important; } }
@media (prefers-reduced-motion: reduce) { .cursor-spark { animation-duration:.85s !important; animation-iteration-count:1 !important; } .cursor-speck { animation-duration:.7s !important; animation-iteration-count:1 !important; } }

/* V8: sửa chữ bị trắng trên nền trắng ở các nút phụ + nút bật/tắt hiệu ứng */
a.btn.btn-secondary, button.btn-secondary { color:#334155 !important; }
a.btn.btn-secondary:hover, button.btn-secondary:hover { color:var(--text) !important; }
a.btn.btn-danger, button.btn-danger { color:#dc2626 !important; }
button.theme-toggle { color:var(--text) !important; background:var(--surface); }
button.theme-toggle:hover { color:var(--primary) !important; background:var(--primary-soft); border-color:var(--primary); }
.chat-quick button { color:var(--text) !important; }
.chat-quick button:hover { color:var(--primary) !important; background:var(--primary-soft); }
/* Nhan trang thai/vai tro luon tren 1 dong, khong bi xuong dong lam lech pill */
.badge { white-space:nowrap; line-height:1.5; vertical-align:middle; }
th { white-space:nowrap; }
td { vertical-align:middle; }
/* Khong dung opacity (lam chu bi nhoe tren Windows/Edge): doi mau truc tiep */
.theme-toggle .theme-label { font-weight:600; }
.fx-toggle .fx-state { font-weight:700; color:var(--primary); }
.fx-toggle.is-off .theme-label { color:#64748b; }
.fx-toggle.is-off .fx-state { color:#64748b; }
.fx-toggle.is-off .theme-icon { filter:grayscale(1); opacity:.6; }
body.dark-mode .fx-toggle.is-off .theme-label, body.dark-mode .fx-toggle.is-off .fx-state { color:#94a3b8; }

/* V9: nut tim yeu thich phai noi tren anh (absolute), khong chen vao chu */
.wish-btn { position:absolute !important; top:46px; right:10px; }

CSS;
    echo '</style></head><body>';
    nav();
    echo '<main class="page-main"><div class="container">';
}

function layout_foot() {
    echo '</div></main>';
    echo '<footer class="site-footer"><div class="container footer-inner">';
    echo '<div><strong>ShopLite</strong> — Hệ thống thương mại điện tử F-CTF</div>';
    echo '<div class="muted">Hotline: 1900 6868 | Miễn phí vận chuyển toàn quốc cho đơn từ 300.000đ</div>';
    echo '</div></footer>';
    echo '<div class="toast" id="siteToast" role="status" aria-live="polite"></div><button class="back-top" id="backTop" aria-label="Lên đầu trang" title="Lên đầu trang">↑</button>';
    echo <<<'CHAT'
<section class="chat-panel" id="shopChatPanel" aria-label="Chat với ShopLite" aria-hidden="true">
  <div class="chat-head"><div class="chat-bot-avatar">🤖</div><div class="chat-head-info"><strong>Trợ lý ShopLite</strong><small>● Đang trực tuyến · Hỗ trợ tự động</small></div><button class="chat-close" id="shopChatClose" aria-label="Đóng chat">×</button></div>
  <div class="chat-messages" id="shopChatMessages" aria-live="polite"><div class="chat-message bot">Xin chào! 👋 Mình là trợ lý ShopLite. Bạn cần tìm sản phẩm, hỏi về giao hàng hay đơn hàng?</div></div>
  <div class="chat-quick"><button type="button" data-chat="giao hàng">🚚 Giao hàng</button><button type="button" data-chat="đổi trả">↩️ Đổi trả</button><button type="button" data-chat="đơn hàng">📦 Đơn hàng</button><button type="button" data-chat="khuyến mãi">🎁 Khuyến mãi</button></div>
  <form class="chat-form" id="shopChatForm"><input id="shopChatInput" type="text" maxlength="300" placeholder="Nhập câu hỏi của bạn..." autocomplete="off" aria-label="Tin nhắn" required><button type="submit" aria-label="Gửi tin nhắn">➤</button></form>
  <div class="chat-note">Trợ lý tự động · Không yêu cầu cung cấp mật khẩu hoặc thông tin thẻ.</div>
</section>
<button class="chat-launcher" id="shopChatLauncher" type="button" aria-label="Mở chat với ShopLite" aria-expanded="false" title="Chat với ShopLite">💬<span class="chat-online"></span></button>
CHAT;
    echo <<<'JS'
<script>
(function () {
  const body = document.body;
  // Hiệu ứng chuột: ngôi sao 4 cánh phát sáng màu xanh da trời đậm.
  const STAR_SVG = '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">'
    + '<path d="M16 0 C16.8 11.2 20.8 15.2 32 16 C20.8 16.8 16.8 20.8 16 32 C15.2 20.8 11.2 16.8 0 16 C11.2 15.2 15.2 11.2 16 0 Z" fill="#0ea5e9"/>'
    + '<path d="M16 4 C16.5 12.2 19.8 15.5 28 16 C19.8 16.5 16.5 19.8 16 28 C15.5 19.8 12.2 16.5 4 16 C12.2 15.5 15.5 12.2 16 4 Z" fill="#bae6fd"/>'
    + '<circle cx="16" cy="16" r="3.4" fill="#ffffff"/></svg>';
  let fxOn = true;
  try { fxOn = localStorage.getItem('shoplite_fx') !== 'off'; } catch (e) {}
  const fxBtn = document.getElementById('fxToggle');
  const applyFx = () => {
    if (!fxBtn) return;
    fxBtn.classList.toggle('is-off', !fxOn);
    fxBtn.setAttribute('aria-pressed', fxOn ? 'true' : 'false');
    const st = fxBtn.querySelector('.fx-state'); if (st) st.textContent = fxOn ? 'Bật' : 'Tắt';
    fxBtn.title = fxOn ? 'Đang bật hiệu ứng chuột — nhấn để tắt' : 'Đang tắt hiệu ứng chuột — nhấn để bật';
  };
  if (fxBtn) fxBtn.addEventListener('click', () => {
    fxOn = !fxOn;
    try { localStorage.setItem('shoplite_fx', fxOn ? 'on' : 'off'); } catch (e) {}
    applyFx();
  });
  applyFx();
  let lastSpark = 0;
  document.addEventListener('pointermove', (event) => {
    if (!fxOn || event.pointerType !== 'mouse') return;
    const now = performance.now(); if (now - lastSpark < 40) return; lastSpark = now;
    const x = event.clientX, y = event.clientY;
    const star = document.createElement('span'); star.className = 'cursor-spark';
    const size = 16 + Math.random() * 14;
    star.style.width = size.toFixed(1) + 'px'; star.style.height = size.toFixed(1) + 'px';
    star.style.left = x + 'px'; star.style.top = y + 'px';
    star.style.setProperty('--dx', ((Math.random() - 0.5) * 24).toFixed(1) + 'px');
    star.style.setProperty('--dy', (20 + Math.random() * 32).toFixed(1) + 'px');
    star.style.setProperty('--rot', ((Math.random() - 0.5) * 28).toFixed(1) + 'deg');
    star.innerHTML = STAR_SVG;
    document.body.appendChild(star); window.setTimeout(() => star.remove(), 880);
    if (Math.random() > 0.4) {
      const speck = document.createElement('span'); speck.className = 'cursor-speck';
      speck.style.left = (x + (Math.random() - 0.5) * 26) + 'px';
      speck.style.top = (y + (Math.random() - 0.5) * 20) + 'px';
      speck.style.setProperty('--dx', ((Math.random() - 0.5) * 28).toFixed(1) + 'px');
      speck.style.setProperty('--dy', (16 + Math.random() * 22).toFixed(1) + 'px');
      document.body.appendChild(speck); window.setTimeout(() => speck.remove(), 720);
    }
  }, {passive:true});
  const toggle = document.getElementById('themeToggle');
  const applyTheme = (theme) => {
    body.classList.toggle('dark-mode', theme === 'dark');
    if (toggle) { const icon = toggle.querySelector('.theme-icon'); if (icon) icon.textContent = theme === 'dark' ? '☀️' : '🌙'; toggle.title = theme === 'dark' ? 'Đang bật giao diện tối — nhấn để chuyển sang sáng' : 'Đang bật giao diện sáng — nhấn để chuyển sang tối'; toggle.setAttribute('aria-label', theme === 'dark' ? 'Bật giao diện sáng' : 'Bật giao diện tối'); }
  };
  let savedTheme = 'light';
  try { savedTheme = localStorage.getItem('shoplite-theme') || 'light'; } catch (e) {}
  applyTheme(savedTheme);
  if (toggle) toggle.addEventListener('click', function () {
    const next = body.classList.contains('dark-mode') ? 'light' : 'dark';
    applyTheme(next); try { localStorage.setItem('shoplite-theme', next); } catch (e) {}
  });
  const backTop = document.getElementById('backTop');
  const updateBackTop = () => { if (backTop) backTop.classList.toggle('visible', window.scrollY > 320); };
  window.addEventListener('scroll', updateBackTop, {passive:true}); updateBackTop();
  if (backTop) backTop.addEventListener('click', () => window.scrollTo({top:0, behavior:'smooth'}));
  const params = new URLSearchParams(window.location.search);
  if (params.has('added') || params.has('wish_added') || params.has('wish_removed')) {
    const toast = document.getElementById('siteToast');
    if (toast) {
      toast.textContent = params.has('added') ? '✓ Đã thêm sản phẩm vào giỏ hàng' : (params.has('wish_added') ? '♡ Đã thêm vào danh sách yêu thích' : 'Đã xóa khỏi danh sách yêu thích');
      toast.classList.add('show');
      window.setTimeout(() => toast.classList.remove('show'), 2800);
    }
    params.delete('added'); params.delete('wish_added'); params.delete('wish_removed');
    const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
    window.history.replaceState({}, '', clean);
  }

  // Chatbot FAQ: rule-based, no external AI/API or sensitive data collection.
  const chatPanel = document.getElementById('shopChatPanel');
  const chatLauncher = document.getElementById('shopChatLauncher');
  const chatClose = document.getElementById('shopChatClose');
  const chatMessages = document.getElementById('shopChatMessages');
  const chatForm = document.getElementById('shopChatForm');
  const chatInput = document.getElementById('shopChatInput');
  const chatStoreKey = 'shoplite-chat-history-v1';
  const escapeChat = (value) => String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const addChatMessage = (text, who, save = true) => {
    if (!chatMessages) return;
    const node = document.createElement('div'); node.className = 'chat-message ' + (who === 'user' ? 'user' : 'bot');
    node.textContent = text; chatMessages.appendChild(node); chatMessages.scrollTop = chatMessages.scrollHeight;
    if (save) { try { const history = JSON.parse(sessionStorage.getItem(chatStoreKey) || '[]'); history.push({text, who}); sessionStorage.setItem(chatStoreKey, JSON.stringify(history.slice(-30))); } catch (e) {} }
  };
  try {
    const history = JSON.parse(sessionStorage.getItem(chatStoreKey) || '[]');
    history.forEach(item => { if (item && typeof item.text === 'string' && ['bot','user'].includes(item.who)) addChatMessage(item.text, item.who, false); });
  } catch (e) {}
  const toggleChat = (open) => { if (!chatPanel || !chatLauncher) return; chatPanel.classList.toggle('open', open); chatPanel.setAttribute('aria-hidden', String(!open)); chatLauncher.setAttribute('aria-expanded', String(open)); if (open && chatInput) setTimeout(() => chatInput.focus(), 80); };
  if (chatLauncher) chatLauncher.addEventListener('click', () => toggleChat(!chatPanel.classList.contains('open')));
  if (chatClose) chatClose.addEventListener('click', () => toggleChat(false));
  const getChatReply = (raw) => {
    const q = raw.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
    if (/xin chao|hello|hi|chao shop|chao/.test(q)) return 'Chào bạn! 😊 ShopLite có thể giúp bạn tìm sản phẩm, xem giỏ hàng, yêu thích hoặc kiểm tra cách theo dõi đơn hàng.';
    if (/giao hang|van chuyen|ship|phi ship|freeship/.test(q)) return '🚚 ShopLite hỗ trợ giao hàng toàn quốc. Đơn từ 300.000đ được miễn phí vận chuyển theo thông tin cửa hàng. Thời gian thực tế tùy khu vực và đơn vị vận chuyển.';
    if (/doi tra|hoan tien|bao hanh|tra hang/.test(q)) return '↩️ Bạn vui lòng giữ lại mã đơn và sản phẩm. Hãy mở mục “Hồ sơ & Đơn hàng” để xem đơn, sau đó liên hệ bộ phận hỗ trợ của cửa hàng để xác nhận điều kiện đổi trả/bảo hành.';
    if (/don hang|theo doi|mua gi|lich su|trang thai/.test(q)) return '📦 Bạn có thể vào “Hồ sơ & Đơn hàng” sau khi đăng nhập để xem sản phẩm đã mua, ngày đặt, tổng tiền và trạng thái đơn hàng.';
    if (/khuyen mai|ma giam|voucher|giam gia/.test(q)) return '🎁 Bạn hãy kiểm tra mã giảm giá đang hiển thị ở trang cửa hàng hoặc nhập mã ở bước thanh toán. Điều kiện áp dụng có thể khác nhau theo từng mã.';
    if (/gio hang|them vao gio/.test(q)) return '🛒 Chọn sản phẩm rồi nhấn “Thêm vào giỏ hàng”. Bạn có thể mở mục Giỏ hàng để chỉnh số lượng và tiếp tục thanh toán.';
    if (/yeu thich|trai tim|wishlist/.test(q)) return '♡ Nhấn biểu tượng trái tim trên sản phẩm để lưu vào danh sách yêu thích. Mở mục “Yêu thích” trên thanh điều hướng để xem lại.';
    if (/san pham|tim|ban gi|con hang|gia/.test(q)) return '🔎 Bạn hãy dùng ô tìm kiếm, bộ lọc danh mục và sắp xếp ở trang chủ để tìm sản phẩm. Nhấn vào sản phẩm để xem giá, mô tả và tình trạng còn hàng.';
    if (/tai khoan|mat khau|dang nhap|avatar|anh dai dien/.test(q)) return '👤 Bạn có thể đăng nhập hoặc đăng ký từ thanh điều hướng. Sau khi đăng nhập, mở “Hồ sơ & Đơn hàng” để cập nhật ảnh đại diện và thông tin liên hệ. Đừng gửi mật khẩu qua chat nhé.';
    if (/cam on|thanks/.test(q)) return 'Rất vui được hỗ trợ bạn! 💙 Còn điều gì bạn muốn hỏi ShopLite không?';
    return 'Mình chưa hiểu chính xác câu hỏi này. Bạn có thể hỏi về sản phẩm, giỏ hàng, giao hàng, đổi trả, khuyến mãi hoặc lịch sử đơn hàng. Nếu cần kiểm tra một đơn cụ thể, hãy mở “Hồ sơ & Đơn hàng”.';
  };
  const sendChat = (text) => { const clean = String(text || '').trim().slice(0,300); if (!clean) return; addChatMessage(clean, 'user'); window.setTimeout(() => addChatMessage(getChatReply(clean), 'bot'), 220); };
  if (chatForm) chatForm.addEventListener('submit', (event) => { event.preventDefault(); const value = chatInput.value; chatInput.value = ''; sendChat(value); });
  document.querySelectorAll('[data-chat]').forEach(button => button.addEventListener('click', () => sendChat(button.getAttribute('data-chat'))));

  // Small entrance effect for cards; content remains visible when reduced motion is preferred.
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const revealTargets = document.querySelectorAll('.grid > *, .grid-2 > *, .grid-3 > *, .grid-4 > *, .review-card, .order-card');
    revealTargets.forEach((el, index) => { el.classList.add('ui-reveal'); el.style.setProperty('--reveal-delay', Math.min(index % 8, 7) * 45 + 'ms'); });
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('ui-revealed'); observer.unobserve(entry.target); }
    }), { threshold: 0.08 });
    revealTargets.forEach(el => observer.observe(el));
  }
})();
</script>
JS;
    echo '</body></html>';
}