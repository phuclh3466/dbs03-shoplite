<?php
require 'db.php';

$msg = '';
$u = $fname = $email = $phone = '';

// Kiem tra do manh mat khau: tra ve danh sach loi (rong = hop le)
function password_problems($p) {
    $errs = [];
    $len = preg_match_all('/./us', $p, $m);
    if ($len === false) { $len = strlen($p); }
    if ($len < 8)                          $errs[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    if (!preg_match('/[a-z]/', $p))        $errs[] = 'Mật khẩu cần có ít nhất 1 chữ cái thường (a-z).';
    if (!preg_match('/[A-Z]/', $p))        $errs[] = 'Mật khẩu cần có ít nhất 1 chữ cái in hoa (A-Z).';
    if (!preg_match('/[0-9]/', $p))        $errs[] = 'Mật khẩu cần có ít nhất 1 chữ số (0-9).';
    if (!preg_match('/[^A-Za-z0-9\s]/', $p)) $errs[] = 'Mật khẩu cần có ít nhất 1 ký tự đặc biệt (ví dụ: ! @ # $ % ^ & *).';
    if (preg_match('/\s/', $p))            $errs[] = 'Mật khẩu không được chứa khoảng trắng.';
    return $errs;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $fname = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];
    if ($u === '') {
        $errors[] = 'Vui lòng nhập tên đăng nhập.';
    }
    if ($fname === '') {
        $errors[] = 'Vui lòng nhập họ và tên.';
    } elseif ((preg_match_all('/./us', $fname) ?: strlen($fname)) < 2) {
        $errors[] = 'Họ và tên quá ngắn, vui lòng nhập đầy đủ.';
    }
    if ($p === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    } else {
        $errors = array_merge($errors, password_problems($p));
    }

    if (!$errors) {
        $c = db();
        $stmt = $c->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$u]);
        if ($stmt->fetch()) {
            $errors[] = 'Tên đăng nhập đã tồn tại.';
        } else {
            // Ho tro tuong thich ca bang users cu (3 cot) lan bang users mo rong
            $stmt = $c->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
            $stmt->execute([$u, $p]);
            $newId = $c->lastInsertId();
            if ($newId) {
                $up = $c->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                if ($up) {
                    $up->execute([$fname, $email, $phone, $newId]);
                }
            }
            header('Location: login.php?registered=1');
            exit;
        }
    }

    if ($errors) {
        $msg = '<div class="err"><strong>Không thể đăng ký, vui lòng kiểm tra lại:</strong><ul style="margin:6px 0 0 18px;padding:0">';
        foreach ($errors as $e) { $msg .= '<li>' . htmlspecialchars($e) . '</li>'; }
        $msg .= '</ul></div>';
    }
}

layout_head('ShopLite - Đăng ký tài khoản');
?>
<style>
  .reg-wrap {
    max-width: 480px;
    margin: 20px auto;
    position: relative;
    z-index: 2;
  }
  .reg-card {
    padding: 30px 28px;
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
    width: 128px;
    height: 120px;
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
  /* Checklist yeu cau mat khau */
  .pw-rules {
    list-style: none;
    margin: 0 0 12px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(239, 246, 255, 0.7);
    border: 1px solid rgba(186, 230, 253, 0.9);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 12px;
    font-size: 0.8rem;
    color: var(--text-muted);
  }
  .pw-rules li { display: flex; align-items: center; gap: 6px; transition: color .15s ease; }
  .pw-rules li::before {
    content: '';
    flex: 0 0 14px; width: 14px; height: 14px; border-radius: 50%;
    border: 1.5px solid #94a3b8; box-sizing: border-box;
    transition: all .15s ease;
  }
  .pw-rules li.met { color: #15803d; font-weight: 600; }
  .pw-rules li.met::before {
    border-color: #16a34a; background: #16a34a;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M4 8.5l2.5 2.5L12 5.5' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-size: 100% 100%;
  }
  @media (max-width: 480px) { .pw-rules { grid-template-columns: 1fr; } }
</style>

<div class="reg-wrap">
  <div class="card reg-card">
    <div style="text-align:center;margin-bottom:18px">
      <!-- Con cu tren trang Dang ky: 2 mat theo doi chuot, nham mat + 2 canh che kin mat khi nhap password -->
      <div class="owl-stage">
        <svg id="regOwlMascot" class="owl-svg" viewBox="0 0 140 130" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="regOwlBodyGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#2563eb"/>
              <stop offset="100%" stop-color="#0ea5e9"/>
            </linearGradient>
            <linearGradient id="regOwlBellyGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#eff6ff"/>
              <stop offset="100%" stop-color="#dbeafe"/>
            </linearGradient>
            <linearGradient id="regOwlWingGrad" x1="0" y1="0" x2="1" y2="1">
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
          <rect x="24" y="20" width="92" height="98" rx="44" fill="url(#regOwlBodyGrad)"/>

          <!-- Bung cu -->
          <ellipse cx="70" cy="86" rx="30" ry="26" fill="url(#regOwlBellyGrad)"/>
          <path d="M58 78 Q62 83 66 78 M74 78 Q78 83 82 78 M64 90 Q68 95 72 90 M52 90 Q56 95 60 90 M80 90 Q84 95 88 90" stroke="#93c5fd" stroke-width="2.2" fill="none" stroke-linecap="round"/>

          <!-- Vien mat -->
          <circle cx="51" cy="54" r="20" fill="#dbeafe" stroke="#1e40af" stroke-width="2.5"/>
          <circle cx="89" cy="54" r="20" fill="#dbeafe" stroke="#1e40af" stroke-width="2.5"/>

          <!-- Long trang mat -->
          <circle cx="51" cy="54" r="16" fill="#ffffff"/>
          <circle cx="89" cy="54" r="16" fill="#ffffff"/>

          <!-- Con nguoi trai (di chuyen theo chuot) -->
          <g id="regPupilLeft">
            <circle cx="51" cy="54" r="7.5" fill="#0f172a"/>
            <circle cx="48.5" cy="51.5" r="2.6" fill="#ffffff"/>
            <circle cx="53.5" cy="56.5" r="1.2" fill="#ffffff"/>
          </g>

          <!-- Con nguoi phai (di chuyen theo chuot) -->
          <g id="regPupilRight">
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
            <ellipse cx="24" cy="78" rx="19" ry="26" fill="url(#regOwlWingGrad)" stroke="#0f172a" stroke-width="1.6"/>
            <path d="M14 68 Q24 74 34 66 M13 78 Q24 84 35 76 M15 88 Q24 94 33 86" stroke="#60a5fa" stroke-width="2" fill="none" stroke-linecap="round"/>
          </g>
          <g class="owl-wing-right">
            <ellipse cx="116" cy="78" rx="19" ry="26" fill="url(#regOwlWingGrad)" stroke="#0f172a" stroke-width="1.6"/>
            <path d="M126 68 Q116 74 106 66 M127 78 Q116 84 105 76 M125 88 Q116 94 107 86" stroke="#60a5fa" stroke-width="2" fill="none" stroke-linecap="round"/>
          </g>
        </svg>
      </div>

      <h1 style="margin:0 0 6px;font-size:1.55rem">Tạo tài khoản mới</h1>
      <p class="muted" style="margin:0">Tham gia ShopLite để nhận nhiều ưu đãi hấp dẫn</p>
    </div>

    <?php echo $msg; ?>

    <form method="post">
      <label for="regUsernameInput">Tên đăng nhập *</label>
      <input id="regUsernameInput" name="username" class="track-input" value="<?php echo htmlspecialchars($u); ?>" placeholder="Chọn tên đăng nhập (viết liền)" autocomplete="username" autofocus required>
      <div id="regUsernameStatus" class="muted" style="font-size:0.82rem;min-height:18px;margin:2px 0 8px"></div>
      <script>
      (function () {
        var input = document.getElementById('regUsernameInput');
        var status = document.getElementById('regUsernameStatus');
        var timer = null;
        input.addEventListener('input', function () {
          clearTimeout(timer);
          var val = input.value.trim();
          if (!val) { status.textContent = ''; return; }
          timer = setTimeout(function () {
            fetch('check_username.php?u=' + encodeURIComponent(val))
              .then(function (r) { return r.json(); })
              .then(function (data) {
                status.textContent = data.taken ? 'Tên đăng nhập đã được sử dụng.' : 'Tên đăng nhập hợp lệ, có thể dùng.';
                status.style.color = data.taken ? '#dc2626' : '#16a34a';
              })
              .catch(function () {});
          }, 300);
        });
      })();
      </script>

      <label for="regPasswordInput">Mật khẩu *</label>
      <div class="pwd-field-wrap">
        <input id="regPasswordInput" name="password" type="password" placeholder="Tạo mật khẩu (tối thiểu 8 ký tự)" autocomplete="new-password" minlength="8" required>
        <button type="button" id="regTogglePwdBtn" class="pwd-toggle-btn" title="Hiện / Ẩn mật khẩu" aria-label="Hiện hoặc ẩn mật khẩu">
          <svg id="regEyeOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          <svg id="regEyeClosed" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          </svg>
        </button>
      </div>

      <ul class="pw-rules" id="pwRules" aria-live="polite">
        <li data-rule="len">Tối thiểu 8 ký tự</li>
        <li data-rule="lower">Có chữ thường (a-z)</li>
        <li data-rule="upper">Có chữ in hoa (A-Z)</li>
        <li data-rule="digit">Có chữ số (0-9)</li>
        <li data-rule="special">Có ký tự đặc biệt (! @ # $ ...)</li>
        <li data-rule="space">Không chứa khoảng trắng</li>
      </ul>

      <label for="regFullNameInput">Họ và tên *</label>
      <input id="regFullNameInput" name="full_name" class="track-input" value="<?php echo htmlspecialchars($fname); ?>" placeholder="Ví dụ: Trần Thị B" autocomplete="name" minlength="2" required>

      <div class="grid-2" style="gap:12px">
        <div>
          <label for="regEmailInput">Email (Tùy chọn)</label>
          <input id="regEmailInput" type="email" name="email" class="track-input" value="<?php echo htmlspecialchars($email); ?>" placeholder="ban@example.com">
        </div>
        <div>
          <label for="regPhoneInput">Số điện thoại (Tùy chọn)</label>
          <input id="regPhoneInput" name="phone" class="track-input" value="<?php echo htmlspecialchars($phone); ?>" placeholder="09xxxxxxxx">
        </div>
      </div>

      <button type="submit" class="btn btn-block" style="margin-top:12px">Đăng ký tài khoản</button>
    </form>

    <p class="muted" style="text-align:center;margin-top:16px">
      Đã có tài khoản? <a href="login.php" style="color:var(--primary);font-weight:600">Đăng nhập</a>
    </p>
  </div>
</div>

<script>
(function() {
  const owl = document.getElementById('regOwlMascot');
  const pupilLeft = document.getElementById('regPupilLeft');
  const pupilRight = document.getElementById('regPupilRight');
  const pwdInput = document.getElementById('regPasswordInput');
  const toggleBtn = document.getElementById('regTogglePwdBtn');
  const iconOpen = document.getElementById('regEyeOpen');
  const iconClosed = document.getElementById('regEyeClosed');
  const trackInputs = document.querySelectorAll('.track-input');

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

  trackInputs.forEach(function(inp) {
    inp.addEventListener('focus', function() {
      isPasswordFocused = false;
      updateOwlWingState();
      lookAtInput(inp);
    });
    inp.addEventListener('input', function() {
      lookAtInput(inp);
    });
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

  // Checklist do manh mat khau (kiem tra truc tiep khi go; server van kiem tra lai)
  const ruleItems = {};
  document.querySelectorAll('#pwRules li').forEach(function(li) { ruleItems[li.dataset.rule] = li; });
  function evaluatePassword() {
    const v = pwdInput.value;
    const checks = {
      len: Array.from(v).length >= 8,
      lower: /[a-z]/.test(v),
      upper: /[A-Z]/.test(v),
      digit: /[0-9]/.test(v),
      special: /[^A-Za-z0-9\s]/.test(v),
      space: v.length > 0 && !/\s/.test(v)
    };
    let all = true;
    Object.keys(checks).forEach(function(k) {
      if (ruleItems[k]) ruleItems[k].classList.toggle('met', checks[k]);
      if (!checks[k]) all = false;
    });
    pwdInput.setCustomValidity(all ? '' : 'Mật khẩu chưa đáp ứng đủ các yêu cầu bên dưới.');
  }
  pwdInput.addEventListener('input', evaluatePassword);
  evaluatePassword();

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