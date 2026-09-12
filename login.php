<?php
/* =========================================================
   HOMI FURNITURES — Login / Register page
   Requires: MySQL running (XAMPP/WAMP) and database.sql imported.
   Submits are handled with fetch() (AJAX) — the page does NOT
   reload; the server answers with JSON and errors show inline.
   ========================================================= */

session_start();
require 'db_connect.php';
require 'csrf.php';

// Already signed in? Go straight to the store.
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

/* ---------- Basic brute-force throttling ---------- */
const MAX_ATTEMPTS   = 5;
const LOCKOUT_SECONDS = 300; // 5 minutes

function too_many_attempts() {
    if (empty($_SESSION['login_attempts'])) return false;
    if (time() - ($_SESSION['login_attempts_time'] ?? 0) > LOCKOUT_SECONDS) {
        $_SESSION['login_attempts'] = 0;
        return false;
    }
    return $_SESSION['login_attempts'] >= MAX_ATTEMPTS;
}
function register_failed_attempt() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts_time'] = time();
}
function clear_failed_attempts() {
    unset($_SESSION['login_attempts'], $_SESSION['login_attempts_time']);
}

/* ---------- Handle form submissions ---------- */
$errors     = [];
$active_tab = 'login';
$old        = ['name' => '', 'email' => ''];

$is_ajax = (($_POST['ajax'] ?? '') === '1');
$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($is_post && !$db) {
    if ($is_ajax) $errors[] = $db_error;
}

if ($is_post && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['form_action'] ?? '';

        try {
            /* ----- REGISTER ----- */
            if ($action === 'register') {
                $active_tab = 'register';
                $name    = trim($_POST['name'] ?? '');
                $email   = trim($_POST['email'] ?? '');
                $pass    = $_POST['password'] ?? '';
                $confirm = $_POST['confirm'] ?? '';

                $old['name']  = $name;
                $old['email'] = $email;

                if ($name === '' || strlen($name) < 2)          $errors[] = 'Please enter your full name.';
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
                if (strlen($pass) < 8)                          $errors[] = 'Password must be at least 8 characters.';
                if ($pass !== $confirm)                         $errors[] = 'Passwords do not match.';

                if (!$errors) {
                    $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $exists = $stmt->get_result()->num_rows > 0;
                    $stmt->close();

                    if ($exists) {
                        $errors[] = 'That email is already registered — try signing in instead.';
                    }
                }

                if (!$errors) {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
                    $stmt->bind_param('sss', $name, $email, $hash);
                    $stmt->execute();
                    $new_id = $db->insert_id;
                    $stmt->close();

                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $new_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'customer';
                    clear_failed_attempts();

                    if ($is_ajax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => true]);
                        exit;
                    }
                    header('Location: index.php');
                    exit;
                }
            }

            /* ----- LOGIN ----- */
            if ($action === 'login') {
                $active_tab = 'login';

                if (too_many_attempts()) {
                    $errors[] = 'Too many failed attempts. Please wait a few minutes and try again.';
                } else {
                    $email = trim($_POST['email'] ?? '');
                    $pass  = $_POST['password'] ?? '';
                    $old['email'] = $email;

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
                    if ($pass === '')                               $errors[] = 'Please enter your password.';

                    if (!$errors) {
                        $stmt = $db->prepare('SELECT user_id, name, password, role FROM users WHERE email = ? LIMIT 1');
                        $stmt->bind_param('s', $email);
                        $stmt->execute();
                        $user = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        if ($user && password_verify($pass, $user['password'])) {
                            session_regenerate_id(true);
                            $_SESSION['user_id']   = (int) $user['user_id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_role'] = $user['role'];
                            clear_failed_attempts();

                            if ($is_ajax) {
                                header('Content-Type: application/json; charset=utf-8');
                                echo json_encode(['ok' => true]);
                                exit;
                            }
                            header('Location: index.php');
                            exit;
                        }

                        register_failed_attempt();
                        $errors[] = 'Incorrect email or password.';
                    }
                }
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Login/register DB error: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

if ($is_post && $is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In / Register — HOMI Furnitures</title>
<link rel="stylesheet" href="styles.css">
</head>
<body class="auth-body">

<a class="auth-back" href="index.php">&larr; Back to store</a>

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-brand">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 11.5 12 4l9 7.5"/>
          <path d="M5.5 10v9a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-9"/>
          <path d="M9.5 20v-6h5v6"/>
        </svg>
      </div>
      <div class="brand-text">
        <div class="name">HOMI</div>
        <div class="sub">FURNITURES</div>
      </div>
    </div>

    <h1 id="authHeading"><?= $active_tab === 'register' ? 'Create your account' : 'Welcome back' ?></h1>
    <p class="auth-sub" id="authSub"><?= $active_tab === 'register'
        ? 'Join HOMI to track orders, save favorites and check out faster.'
        : 'Sign in to your HOMI account for order tracking and saved favorites.' ?></p>

    <?php if ($db_error): ?>
      <div class="auth-alert auth-alert-error"><?= e($db_error) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="auth-alert auth-alert-error" id="authAlert">
        <?php foreach ($errors as $msg): ?><p><?= e($msg) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="auth-tabs">
      <button type="button" class="auth-tab<?= $active_tab === 'login' ? ' active' : '' ?>" data-tab="login">Sign In</button>
      <button type="button" class="auth-tab<?= $active_tab === 'register' ? ' active' : '' ?>" data-tab="register">Create Account</button>
    </div>

    <!-- ===== Sign in ===== -->
    <form id="loginForm" class="auth-form<?= $active_tab === 'login' ? ' active' : '' ?>" method="post" action="login.php">
      <input type="hidden" name="form_action" value="login">
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <div class="form-field">
        <label for="loginEmail">Email</label>
        <input type="email" id="loginEmail" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
      </div>
      <div class="form-field">
        <label for="loginPassword">Password</label>
        <div class="pass-wrap">
          <input type="password" id="loginPassword" name="password" autocomplete="current-password" required>
          <button type="button" class="pass-toggle" data-target="loginPassword">Show</button>
        </div>
      </div>
      <button type="submit" class="btn btn-green btn-block">SIGN IN</button>
      <p class="auth-switch">New to HOMI? <button type="button" class="link-btn" data-goto="register">Create an account</button></p>
    </form>

    <!-- ===== Register ===== -->
    <form id="registerForm" class="auth-form<?= $active_tab === 'register' ? ' active' : '' ?>" method="post" action="login.php">
      <input type="hidden" name="form_action" value="register">
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <div class="form-field">
        <label for="regName">Full Name</label>
        <input type="text" id="regName" name="name" value="<?= e($old['name']) ?>" autocomplete="name" required>
      </div>
      <div class="form-field">
        <label for="regEmail">Email</label>
        <input type="email" id="regEmail" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
      </div>
      <div class="form-field">
        <label for="regPassword">Password</label>
        <div class="pass-wrap">
          <input type="password" id="regPassword" name="password" autocomplete="new-password" minlength="8" required>
          <button type="button" class="pass-toggle" data-target="regPassword">Show</button>
        </div>
        <small class="field-hint">Use at least 8 characters.</small>
      </div>
      <div class="form-field">
        <label for="regConfirm">Confirm Password</label>
        <input type="password" id="regConfirm" name="confirm" autocomplete="new-password" required>
      </div>
      <button type="submit" class="btn btn-green btn-block">CREATE ACCOUNT</button>
      <p class="auth-switch">Already have an account? <button type="button" class="link-btn" data-goto="login">Sign in</button></p>
    </form>

  </div>
</div>

<script>
(function () {
  var heading = document.getElementById('authHeading');
  var sub     = document.getElementById('authSub');
  var copy = {
    login: { h: 'Welcome back', s: 'Sign in to your HOMI account for order tracking and saved favorites.' },
    register: { h: 'Create your account', s: 'Join HOMI to track orders, save favorites and check out faster.' }
  };

  function showTab(name) {
    document.querySelectorAll('.auth-tab').forEach(function (t) {
      t.classList.toggle('active', t.dataset.tab === name);
    });
    document.querySelectorAll('.auth-form').forEach(function (f) {
      f.classList.toggle('active', f.id === name + 'Form');
    });
    heading.textContent = copy[name].h;
    sub.textContent = copy[name].s;
    hideAlert();
  }

  document.querySelectorAll('.auth-tab').forEach(function (t) {
    t.addEventListener('click', function () { showTab(this.dataset.tab); });
  });
  document.querySelectorAll('[data-goto]').forEach(function (b) {
    b.addEventListener('click', function () { showTab(this.dataset.goto); });
  });

  document.querySelectorAll('.pass-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(this.dataset.target);
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      this.textContent = show ? 'Hide' : 'Show';
    });
  });

  var tabs = document.querySelector('.auth-tabs');

  function showAlert(messages) {
    hideAlert();
    var box = document.createElement('div');
    box.className = 'auth-alert auth-alert-error';
    box.id = 'authAlert';
    messages.forEach(function (m) {
      var p = document.createElement('p');
      p.textContent = m;
      box.appendChild(p);
    });
    tabs.parentNode.insertBefore(box, tabs);
  }
  function hideAlert() {
    var existing = document.getElementById('authAlert');
    if (existing) existing.remove();
  }

  function handleSubmit(form) {
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var btn = form.querySelector('button[type="submit"]');
    var original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'PLEASE WAIT…';

    var data = new FormData(form);
    data.append('ajax', '1');

    fetch(form.action, { method: 'POST', body: data })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (json && json.ok) {
          btn.textContent = 'SUCCESS — OPENING STORE…';
          window.location.replace('index.php');
          return;
        }
        showAlert(json && json.errors && json.errors.length
          ? json.errors
          : ['Something went wrong. Please try again.']);
        btn.disabled = false;
        btn.textContent = original;
      })
      .catch(function () {
        showAlert(['Could not reach the server. Make sure the PHP server and MySQL are both running.']);
        btn.disabled = false;
        btn.textContent = original;
      });
  }

  document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();
    handleSubmit(this);
  });
  document.getElementById('registerForm').addEventListener('submit', function (e) {
    e.preventDefault();
    handleSubmit(this);
  });
})();
</script>
</body>
</html>