<?php
require 'db.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $c = db();
    // ===== LO HONG: SQL Injection - Authentication Bypass (CWE-89 / CWE-287) =====
    // Khong dung prepared statement nhu truoc, ma noi chuoi truc tiep $u, $p
    // vao cau SQL. Payload kinh dien: username = admin' -- , password = bat ky
    // -> cau WHERE con lai la "username = 'admin'" (phan sau bi comment hoa boi --),
    // dang nhap duoc ma KHONG CAN biet mat khau that.
    // Co lap pham vi Flag #1: chi cho phep ky thuat Authentication Bypass tren bang users,
    // chan UNION SELECT / subquery doc cheo sang cac bang flag khac hoac gia mao session.
    $combined = $u . ' ' . $p;
    if (preg_match('/(union|select|from|join|pragma|sqlite_|flag_level|secrets|orders)/i', $combined)) {
        $row = false;
    } else {
        $sql = "SELECT id, username, role FROM users WHERE username = '" . $u . "' AND password = '" . $p . "'";
        try {
            $row = $c->query($sql)->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $row = false;
        }
    }
    if ($row && !empty($row['id'])) {
        // Doi chieu lai id voi bang users that de dam bao khong gia mao duoc username/role tu du lieu ngoai
        $verify = $c->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $verify->execute([(int)$row['id']]);
        $realUser = $verify->fetch(PDO::FETCH_ASSOC);
        if ($realUser) {
            $_SESSION['uid'] = $realUser['id'];
            $_SESSION['username'] = $realUser['username'];
            $_SESSION['role'] = $realUser['role'];
            header('Location: index.php');
            exit;
        }
    }
    $msg = '<p class="err">Sai tên đăng nhập hoặc mật khẩu.</p>';
}

layout_head('ShopLite - Đăng nhập');
?>
<style>
  .login-wrap {
    max-width: 440px;
    margin: 24px auto;
    position: relative;
    z-index: 2;
  }
  .login-card {
    padding: 32px 28px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    box-shadow: 0 14px 36px -8px rgba(14, 165, 233, 0.14), 0 4px 12px -2px rgba(15, 23, 42, 0.05);
    border: 1px solid rgba(186, 230, 253, 0.85);
  }
  .owl-stage {
    display: flex;
    justify-content: center;
    margin-bottom: 10px;
  }
  .owl-svg {
    width: 132px;
    height: 124px;
    overflow: visible;
    filter: drop-shadow(0 8px 16px rgba(37, 99, 235, 0.18));
    user-select: none;
  }

  /* Nham mat (Eyelids) */
  .owl-closed-eye-left,
  .owl-closed-eye-right {
    opacity: 0;
    transition: opacity 0.22s ease;
    pointer-events: none;
  }
  .owl-svg.covering-eyes .owl-closed-eye-left,
  .owl-svg.covering-eyes .owl-closed-eye-right {
    opacity: 1;
  }
  .owl-svg.peeking .owl-closed-eye-left {
    opacity: 1;
  }
  .owl-svg.peeking .owl-closed-eye-right {
    opacity: 0;
  }

  /* Canh cu xoay quanh tam cua chinh chiec canh (fill-box) de de khit len tam 2 mat */
  .owl-wing-left,
  .owl-wing-right {
    transform-box: fill-box;
    transform-origin: center center;
    transition: transform 0.36s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  .owl-wing-left {
    transform: translate(0px, 0px) rotate(0deg) scale(1);
  }
  .owl-wing-right {
    transform: translate(0px, 0px) rotate(0deg) scale(1);
  }

  /* Trang thai che kin toan bo 2 mat */
  .owl-svg.covering-eyes .owl-wing-left {
    transform: translate(27px, -24px) rotate(36deg) scale(1.12);
  }
  .owl-svg.covering-eyes .owl-wing-right {
    transform: translate(-27px, -24px) rotate(-36deg) scale(1.12);
  }

  /* Trang thai he 1 mat nhin trom khi bam xem password */
  .owl-svg.peeking .owl-wing-left {
    transform: translate(27px, -24px) rotate(36deg) scale(1.12);
  }
  .owl-svg.peeking .owl-wing-right {
    transform: translate(-10px, -8px) rotate(-14deg) scale(1.02);
  }

  /* O mat khau & nut con mat co dinh */
  .pwd-field-wrap {
    position: relative;
    width: 100%;
    margin: 4px 0 10px;
  }
  .pwd-field-wrap input {
    width: 100%;
    margin: 0 !important;
    padding-right: 46px !important;
    box-sizing: border-box;
  }
  .pwd-field-wrap input::-ms-reveal,
  .pwd-field-wrap input::-ms-clear {
    display: none;
  }
  .pwd-toggle-btn {
    position: absolute !important;
    right: 6px !important;
    top: 0 !important;
    bottom: 0 !important;
    margin: auto 0 !important;
    width: 34px !important;
    height: 34px !important;
    padding: 0 !important;
    border-radius: 8px !important;
    background: transparent !important;
    color: #64748b !important;
    border: none !important;
    box-shadow: none !important;
    transform: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer;
    z-index: 3;
    transition: background-color 0.15s ease, color 0.15s ease !important;
  }
  .pwd-toggle-btn:hover,
  .pwd-toggle-btn:focus,
  .pwd-toggle-btn:active {
    background: #e2e8f0 !important;
    color: #1e293b !important;
    transform: none !important;
    top: 0 !important;
    bottom: 0 !important;
    margin: auto 0 !important;
  }
  .pwd-toggle-btn svg {
    width: 20px;
    height: 20px;
    display: block;
    pointer-events: none;
  }
</style>

<div class="login-wrap">
  <div class="card login-card">
    <div style="text-align:center;margin-bottom:20px">
      <!-- Con cu: 2 mat theo doi chuot, khi nhap password thi nham mat + 2 canh che kin mat -->
      <div class="owl-stage">
        <svg id="owlMascot" class="owl-svg" viewBox="0 0 140 130" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="owlBodyGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#2563eb"/>
              <stop offset="100%" stop-color="#0ea5e9"/>
            </linearGradient>
            <linearGradient id="owlBellyGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#eff6ff"/>
              <stop offset="100%" stop-color="#dbeafe"/>
            </linearGradient>
            <linearGradient id="owlWingGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#1d4ed8"/>
              <stop offset="100%" stop-color="#1e3a8a"/>
            </linearGradient>
          </defs>

          <!-- Hai chan -->
          <g fill="#f59e0b">
            <rect x="46" y="114" width="6" height="10" rx="3"/>
            <rect x="53" y="114" width="6" height="10" rx="3"/>
            <rect x="81" y="114" width="6" height="10" rx="3"/>
            <rect x="88" y="114" width="6" height="10" rx="3"/>
          </g>

          <!-- Tai cu -->
          <path d="M32 34 L22 14 L48 24 Z" fill="#1d4ed8"/>
          <path d="M108 34 L118 14 L92 24 Z" fill="#1d4ed8"/>

          <!-- Than cu -->
          <rect x="24" y="20" width="92" height="98" rx="44" fill="url(#owlBodyGrad)"/>

          <!-- Bung cu -->
          <ellipse cx="70" cy="86" rx="30" ry="26" fill="url(#owlBellyGrad)"/>
          <path d="M58 78 Q62 83 66 78 M74 78 Q78 83 82 78 M64 90 Q68 95 72 90 M52 90 Q56 95 60 90 M80 90 Q84 95 88 90" stroke="#93c5fd" stroke-width="2.2" fill="none" stroke-linecap="round"/>

          <!-- Vien mat -->
          <circle cx="51" cy="54" r="20" fill="#dbeafe" stroke="#1e40af" stroke-width="2.5"/>
          <circle cx="89" cy="54" r="20" fill="#dbeafe" stroke="#1e40af" stroke-width="2.5"/>

          <!-- Long trang mat -->
          <circle cx="51" cy="54" r="16" fill="#ffffff"/>
          <circle cx="89" cy="54" r="16" fill="#ffffff"/>

          <!-- Con nguoi trai (di chuyen theo chuot) -->
          <g id="pupilLeft">
            <circle cx="51" cy="54" r="7.5" fill="#0f172a"/>
            <circle cx="48.5" cy="51.5" r="2.6" fill="#ffffff"/>
            <circle cx="53.5" cy="56.5" r="1.2" fill="#ffffff"/>
          </g>

          <!-- Con nguoi phai (di chuyen theo chuot) -->
          <g id="pupilRight">
            <circle cx="89" cy="54" r="7.5" fill="#0f172a"/>
            <circle cx="86.5" cy="51.5" r="2.6" fill="#ffffff"/>
            <circle cx="91.5" cy="56.5" r="1.2" fill="#ffffff"/>
          </g>

          <!-- Lop Mi Mat Nham Lai (hien len khi nhap mat khau) -->
          <g class="owl-closed-eye-left">
            <circle cx="51" cy="54" r="16.5" fill="#bfdbfe"/>
            <path d="M39 53 Q51 63 63 53" fill="none" stroke="#1e3a8a" stroke-width="3.2" stroke-linecap="round"/>
            <line x1="42" y1="57" x2="39" y2="61" stroke="#1e3a8a" stroke-width="2.2" stroke-linecap="round"/>
            <line x1="51" y1="59" x2="51" y2="64" stroke="#1e3a8a" stroke-width="2.2" stroke-linecap="round"/>
            <line x1="60" y1="57" x2="63" y2="61" stroke="#1e3a8a" stroke-width="2.2" stroke-linecap="round"/>
          </g>

          <g class="owl-closed-eye-right">
            <circle cx="89" cy="54" r="16.5" fill="#bfdbfe"/>
            <path d="M77 53 Q89 63 101 53" fill="none" stroke="#1e3a8a" stroke-width="3.2" stroke-linecap="round"/>
            <line x1="80" y1="57" x2="77" y2="61" stroke="#1e3a8a" stroke-width="2.2" stroke-linecap="round"/>
            <line x1="89" y1="59" x2="89" y2="64" stroke="#1e3a8a" stroke-width="2.2" stroke-linecap="round"/>
            <line x1="98" y1="57" x2="101" y2="61" stroke="#1e3a8a" stroke-width="2.2" stroke-linecap="round"/>
          </g>

          <!-- Mo cu -->
          <path d="M64 60 L76 60 L70 71 Z" fill="#f59e0b" stroke="#d97706" stroke-width="1.5" stroke-linejoin="round"/>

          <!-- Ma hong -->
          <ellipse cx="34" cy="68" rx="5" ry="3" fill="#7dd3fc" fill-opacity="0.45"/>
          <ellipse cx="106" cy="68" rx="5" ry="3" fill="#7dd3fc" fill-opacity="0.45"/>

          <!-- Canh trai & phai -->
          <g class="owl-wing-left">
            <ellipse cx="24" cy="78" rx="19" ry="26" fill="url(#owlWingGrad)" stroke="#0f172a" stroke-width="1.6"/>
            <path d="M14 68 Q24 74 34 66 M13 78 Q24 84 35 76 M15 88 Q24 94 33 86" stroke="#60a5fa" stroke-width="2" fill="none" stroke-linecap="round"/>
          </g>
          <g class="owl-wing-right">
            <ellipse cx="116" cy="78" rx="19" ry="26" fill="url(#owlWingGrad)" stroke="#0f172a" stroke-width="1.6"/>
            <path d="M126 68 Q116 74 106 66 M127 78 Q116 84 105 76 M125 88 Q116 94 107 86" stroke="#60a5fa" stroke-width="2" fill="none" stroke-linecap="round"/>
          </g>
        </svg>
      </div>

      <h1 style="margin:0 0 6px;font-size:1.55rem">Đăng nhập ShopLite</h1>
      <p class="muted" style="margin:0">Đăng nhập để quản lý giỏ hàng và theo dõi đơn hàng</p>
    </div>

    <?php if (!empty($_GET['registered'])): ?>
      <p class="ok">Đăng ký tài khoản thành công! Mời bạn đăng nhập.</p>
    <?php endif; ?>
    <?php echo $msg; ?>

    <form method="post">
      <label for="usernameInput">Tên đăng nhập</label>
      <input id="usernameInput" name="username" placeholder="Nhập username của bạn" autofocus required>

      <label for="passwordInput">Mật khẩu</label>
      <div class="pwd-field-wrap">
        <input id="passwordInput" name="password" type="password" placeholder="Nhập mật khẩu" required>
        <button type="button" id="togglePwdBtn" class="pwd-toggle-btn" title="Hiện / Ẩn mật khẩu" aria-label="Hiện hoặc ẩn mật khẩu">
          <svg id="iconEyeOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          <svg id="iconEyeClosed" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          </svg>
        </button>
      </div>
      <p style="text-align:right;margin:-4px 0 0">
        <a href="forgot_password.php" style="font-size:0.85rem;color:var(--primary)">Quên mật khẩu?</a>
      </p>

      <button type="submit" class="btn btn-block" style="margin-top:14px">Đăng nhập</button>
    </form>

    <p class="muted" style="text-align:center;margin-top:16px">
      Chưa có tài khoản? <a href="register.php" style="color:var(--primary);font-weight:600">Đăng ký ngay</a>
    </p>
  </div>
</div>

<script>
(function() {
  const owl = document.getElementById('owlMascot');
  const pupilLeft = document.getElementById('pupilLeft');
  const pupilRight = document.getElementById('pupilRight');
  const pwdInput = document.getElementById('passwordInput');
  const userInput = document.getElementById('usernameInput');
  const toggleBtn = document.getElementById('togglePwdBtn');
  const iconOpen = document.getElementById('iconEyeOpen');
  const iconClosed = document.getElementById('iconEyeClosed');

  const maxOffset = 6.5;
  let isPasswordFocused = false;

  function trackEyesToPoint(clientX, clientY) {
    const rect = owl.getBoundingClientRect();
    if (!rect.width || !rect.height) return;

    const leftCenterX = rect.left + (51 / 140) * rect.width;
    const leftCenterY = rect.top + (54 / 130) * rect.height;

    const rightCenterX = rect.left + (89 / 140) * rect.width;
    const rightCenterY = rect.top + (54 / 130) * rect.height;

    const dxL = clientX - leftCenterX;
    const dyL = clientY - leftCenterY;
    const angleL = Math.atan2(dyL, dxL);
    const distL = Math.min(maxOffset, Math.hypot(dxL, dyL) / 14);
    const txL = Math.cos(angleL) * distL;
    const tyL = Math.sin(angleL) * distL;

    const dxR = clientX - rightCenterX;
    const dyR = clientY - rightCenterY;
    const angleR = Math.atan2(dyR, dxR);
    const distR = Math.min(maxOffset, Math.hypot(dxR, dyR) / 14);
    const txR = Math.cos(angleR) * distR;
    const tyR = Math.sin(angleR) * distR;

    pupilLeft.setAttribute('transform', 'translate(' + txL.toFixed(2) + ',' + tyL.toFixed(2) + ')');
    pupilRight.setAttribute('transform', 'translate(' + txR.toFixed(2) + ',' + tyR.toFixed(2) + ')');
  }

  function updateOwlWingState() {
    if (isPasswordFocused) {
      if (pwdInput.type === 'password') {
        owl.classList.add('covering-eyes');
        owl.classList.remove('peeking');
      } else {
        owl.classList.remove('covering-eyes');
        owl.classList.add('peeking');
      }
    } else {
      owl.classList.remove('covering-eyes', 'peeking');
    }
  }

  document.addEventListener('mousemove', function(e) {
    trackEyesToPoint(e.clientX, e.clientY);
  });

  function lookAtInput(inputEl) {
    const rect = inputEl.getBoundingClientRect();
    const caretOffset = Math.min(rect.width - 30, (inputEl.value.length || 0) * 9 + 24);
    trackEyesToPoint(rect.left + caretOffset, rect.top + rect.height / 2);
  }

  userInput.addEventListener('focus', function() {
    isPasswordFocused = false;
    updateOwlWingState();
    lookAtInput(userInput);
  });
  userInput.addEventListener('input', function() {
    lookAtInput(userInput);
  });

  pwdInput.addEventListener('focus', function() {
    isPasswordFocused = true;
    updateOwlWingState();
  });

  pwdInput.addEventListener('blur', function() {
    setTimeout(function() {
      if (document.activeElement !== pwdInput && document.activeElement !== toggleBtn) {
        isPasswordFocused = false;
        updateOwlWingState();
      }
    }, 80);
  });

  toggleBtn.addEventListener('mousedown', function(e) {
    e.preventDefault();
  });

  toggleBtn.addEventListener('click', function() {
    const isHidden = pwdInput.type === 'password';
    pwdInput.type = isHidden ? 'text' : 'password';
    iconOpen.style.display = isHidden ? 'none' : 'block';
    iconClosed.style.display = isHidden ? 'block' : 'none';
    isPasswordFocused = true;
    updateOwlWingState();
    pwdInput.focus();
  });
})();
</script>
<?php layout_foot(); ?>