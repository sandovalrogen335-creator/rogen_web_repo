/* =========================================================
   HOMI FURNITURES — login.html interactions
   Server-free: accounts live in the browser's localStorage,
   so this page works even when opened directly (file://).
   Pressing SIGN IN / CREATE ACCOUNT never reloads the page —
   errors appear inline, success redirects via JavaScript.
   ========================================================= */

(function () {
  'use strict';

  /* ---------- Tab switching (Sign In / Create Account) ---------- */
  var heading  = document.getElementById('authHeading');
  var sub      = document.getElementById('authSub');
  var alertBox = document.getElementById('authAlert');
  var copy = {
    login: {
      h: 'Welcome back',
      s: 'Sign in to your HOMI account for order tracking and saved favorites.'
    },
    register: {
      h: 'Create your account',
      s: 'Join HOMI to track orders, save favorites and check out faster.'
    }
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

  /* ---------- Show / hide password ---------- */
  document.querySelectorAll('.pass-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(this.dataset.target);
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      this.textContent = show ? 'Hide' : 'Show';
    });
  });

  /* ---------- Inline error box (no page reload, ever) ---------- */
  function showAlert(messages) {
    alertBox.innerHTML = '';
    messages.forEach(function (m) {
      var p = document.createElement('p');
      p.textContent = m;
      alertBox.appendChild(p);
    });
    alertBox.style.display = 'block';
  }
  function hideAlert() {
    alertBox.style.display = 'none';
    alertBox.innerHTML = '';
  }

  /* ---------- Browser storage helpers ---------- */
  function loadUsers() {
    try {
      return JSON.parse(window.localStorage.getItem('homi_users') || '[]') || [];
    } catch (e) { return []; }
  }
  function saveUsers(users) {
    try { window.localStorage.setItem('homi_users', JSON.stringify(users)); } catch (e) {}
  }
  function setCurrentUser(name) {
    try { window.localStorage.setItem('homi_user', name); } catch (e) {}
  }

  /* ---------- Password hashing (never stored as plain text) ---------- */
  function fallbackHash(str) {
    var h1 = 0xdeadbeef, h2 = 0x41c6ce57, i, ch;
    for (i = 0; i < str.length; i++) {
      ch = str.charCodeAt(i);
      h1 = Math.imul(h1 ^ ch, 2654435761);
      h2 = Math.imul(h2 ^ ch, 1597334677);
    }
    h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
    h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);
    return (4294967296 * (2097151 & h2) + (h1 >>> 0)).toString(16) + (h2 >>> 0).toString(16);
  }
  function hashPassword(password, salt) {
    if (window.crypto && window.crypto.subtle && window.TextEncoder) {
      return window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(salt + password))
        .then(function (buf) {
          return Array.prototype.map.call(new Uint8Array(buf), function (b) {
            return ('0' + b.toString(16)).slice(-2);
          }).join('');
        })
        .catch(function () { return fallbackHash(salt + password); });
    }
    return Promise.resolve(fallbackHash(salt + password));
  }
  function makeSalt() {
    return Math.random().toString(36).slice(2) + Date.now().toString(36);
  }

  /* ---------- Busy state on the submit button ---------- */
  var busy = false;
  function setBusy(form, isBusy) {
    var btn = form.querySelector('button[type="submit"]');
    if (isBusy) {
      btn.dataset.label = btn.textContent;
      btn.textContent = 'PLEASE WAIT…';
      btn.disabled = true;
    } else {
      btn.textContent = btn.dataset.label || btn.textContent;
      btn.disabled = false;
    }
  }

  function validEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

  /* ---------- Register ---------- */
  async function doRegister(form) {
    var name    = form.elements['name'].value.trim();
    var email   = form.elements['email'].value.trim().toLowerCase();
    var pass    = form.elements['password'].value;
    var confirm = form.elements['confirm'].value;

    var errors = [];
    if (name.length < 2)    errors.push('Please enter your full name.');
    if (!validEmail(email)) errors.push('Please enter a valid email address.');
    if (pass.length < 8)    errors.push('Password must be at least 8 characters.');
    if (pass !== confirm)   errors.push('Passwords do not match.');

    var users = loadUsers();
    if (!errors.length) {
      for (var i = 0; i < users.length; i++) {
        if (users[i].email === email) {
          errors.push('That email is already registered — try signing in instead.');
          break;
        }
      }
    }

    if (errors.length) { showAlert(errors); setBusy(form, false); return; }

    var salt = makeSalt();
    var hash = await hashPassword(pass, salt);
    users.push({ name: name, email: email, salt: salt, hash: hash });
    saveUsers(users);
    setCurrentUser(name);              // auto sign-in
    window.location.replace('index.html');
  }

  /* ---------- Login ---------- */
  async function doLogin(form) {
    var email = form.elements['email'].value.trim().toLowerCase();
    var pass  = form.elements['password'].value;

    var errors = [];
    if (!validEmail(email)) errors.push('Please enter a valid email address.');
    if (!pass)              errors.push('Please enter your password.');
    if (errors.length) { showAlert(errors); setBusy(form, false); return; }

    var users = loadUsers();
    var user = null;
    for (var i = 0; i < users.length; i++) {
      if (users[i].email === email) { user = users[i]; break; }
    }

    if (!user) { showAlert(['Incorrect email or password.']); setBusy(form, false); return; }

    var hash = await hashPassword(pass, user.salt);
    if (hash !== user.hash) {
      showAlert(['Incorrect email or password.']);
      setBusy(form, false);
      return;
    }

    setCurrentUser(user.name);
    window.location.replace('index.html');
  }

  /* ---------- Submit handling — preventDefault = NO reload/download ---------- */
  function handleSubmit(form, mode) {
    if (!form.checkValidity()) { form.reportValidity(); return; }
    if (busy) return;
    busy = true;
    setBusy(form, true);
    hideAlert();

    var run = (mode === 'login') ? doLogin(form) : doRegister(form);
    Promise.resolve(run)
      .catch(function () {
        showAlert(['Something went wrong. Please try again.']);
        setBusy(form, false);
      })
      .then(function () { busy = false; });
  }

  document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();                  // stop the page from reloading
    handleSubmit(this, 'login');
  });
  document.getElementById('registerForm').addEventListener('submit', function (e) {
    e.preventDefault();                  // stop the page from reloading
    handleSubmit(this, 'register');
  });
})();