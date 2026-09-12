/* =========================================================
   HOMI FURNITURES — site interactions
   Everything below is plain, vanilla JavaScript. No backend
   is wired up, so "submitting" a form or "checking out" a
   cart simply confirms the action with a toast message —
   swap those spots for real API calls when you have one.
   ========================================================= */

/* ---------- Toasts ---------- */
function showToast(message) {
  var container = document.getElementById('toastContainer');
  var toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = message;
  container.appendChild(toast);
  requestAnimationFrame(function () { toast.classList.add('show'); });
  setTimeout(function () {
    toast.classList.remove('show');
    setTimeout(function () { toast.remove(); }, 300);
  }, 3200);
}

/* ---------- Modal system (shared by quote / contact / details) ---------- */
var openModalIds = [];
var cartOpen = false;

function lockScrollIfNeeded() {
  document.body.style.overflow = 'hidden';
}
function unlockScrollIfIdle() {
  if (openModalIds.length === 0 && !cartOpen) {
    document.body.style.overflow = '';
  }
}
function openModal(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.add('open');
  openModalIds.push(id);
  lockScrollIfNeeded();
}
function closeModal(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('open');
  openModalIds = openModalIds.filter(function (m) { return m !== id; });
  unlockScrollIfIdle();
}

document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
  btn.addEventListener('click', function () { closeModal(this.dataset.closeModal); });
});
document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

/* ---------- Header action buttons ---------- */
document.getElementById('quoteBtn').addEventListener('click', function () {
  openModal('quoteModal');
});

document.getElementById('sendMessageBtn').addEventListener('click', function () {
  openModal('contactModal');
});

/* ---------- Quote form ---------- */
document.getElementById('quoteForm').addEventListener('submit', function (e) {
  e.preventDefault();
  if (!this.checkValidity()) { this.reportValidity(); return; }
  var form = this;
  var data = new FormData(form);
  data.append('type', 'quote');
  fetch('submit_form.php', { method: 'POST', body: data })
    .then(function (res) { return res.json(); })
    .then(function (json) {
      if (json && json.ok) {
        showToast("Thanks! Your quote request has been sent — we'll reach out within 24 hours.");
        form.reset();
        closeModal('quoteModal');
      } else {
        showToast((json && json.errors && json.errors[0]) || 'Something went wrong. Please try again.');
      }
    })
    .catch(function () {
      showToast('Could not reach the server. Please try again.');
    });
});

/* ---------- Contact form ---------- */
document.getElementById('contactForm').addEventListener('submit', function (e) {
  e.preventDefault();
  if (!this.checkValidity()) { this.reportValidity(); return; }
  var form = this;
  var data = new FormData(form);
  data.append('type', 'contact');
  data.append('csrf_token', HOMI_CSRF);
  fetch('submit_form.php', { method: 'POST', body: data })
    .then(function (res) { return res.json(); })
    .then(function (json) {
      if (json && json.ok) {
        showToast('Message sent! Our team will get back to you shortly.');
        form.reset();
        closeModal('contactModal');
      } else {
        showToast((json && json.errors && json.errors[0]) || 'Something went wrong. Please try again.');
      }
    })
    .catch(function () {
      showToast('Could not reach the server. Please try again.');
    });
});

/* ---------- Collection "View Details" modal ---------- */
var detailsCollectionName = '';
document.querySelectorAll('.view-details-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var img = document.getElementById('detailsImg');
    img.src = this.dataset.img;
    img.alt = this.dataset.name;
    document.getElementById('detailsTitle').textContent = this.dataset.name;
    document.getElementById('detailsDesc').textContent = this.dataset.desc;
    detailsCollectionName = this.dataset.name;
    openModal('detailsModal');
  });
});
document.getElementById('detailsQuoteBtn').addEventListener('click', function () {
  closeModal('detailsModal');
  var select = document.getElementById('quoteInterest');
  for (var i = 0; i < select.options.length; i++) {
    if (select.options[i].text === detailsCollectionName) {
      select.selectedIndex = i;
      break;
    }
  }
  openModal('quoteModal');
});

/* ---------- Cart ---------- */
var cart = [];

function openCart() {
  document.getElementById('cartOverlay').classList.add('open');
  cartOpen = true;
  lockScrollIfNeeded();
}
function closeCart() {
  document.getElementById('cartOverlay').classList.remove('open');
  cartOpen = false;
  unlockScrollIfIdle();
}
function updateCartCount() {
  var count = cart.reduce(function (sum, i) { return sum + i.qty; }, 0);
  document.getElementById('cartCount').textContent = count;
}
function renderCart() {
  var wrap = document.getElementById('cartItems');
  wrap.innerHTML = '';
  if (cart.length === 0) {
    wrap.innerHTML = '<div class="cart-empty">Your cart is empty.</div>';
  } else {
    cart.forEach(function (item, idx) {
      var row = document.createElement('div');
      row.className = 'cart-item';
      row.innerHTML =
        '<img src="' + item.img + '" alt="' + item.name + '">' +
        '<div class="cart-item-info">' +
          '<div class="name">' + item.name + '</div>' +
          '<div class="price">$' + item.price.toLocaleString() + '</div>' +
          '<div class="qty-controls">' +
            '<button type="button" data-action="dec" data-idx="' + idx + '" aria-label="Decrease quantity">−</button>' +
            '<span>' + item.qty + '</span>' +
            '<button type="button" data-action="inc" data-idx="' + idx + '" aria-label="Increase quantity">+</button>' +
          '</div>' +
          '<button type="button" class="cart-remove" data-action="remove" data-idx="' + idx + '">Remove</button>' +
        '</div>';
      wrap.appendChild(row);
    });
  }
  var subtotal = cart.reduce(function (sum, i) { return sum + i.price * i.qty; }, 0);
  document.getElementById('cartSubtotal').textContent = '$' + subtotal.toLocaleString();
}
function addToCart(product) {
  var existing = cart.filter(function (i) { return i.name === product.name; })[0];
  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({ name: product.name, price: product.price, img: product.img, qty: 1 });
  }
  renderCart();
  updateCartCount();
  showToast(product.name + ' added to your cart');
  openCart();

  syncCartAdd(product).then(function (res) {
    if (res && res.ok && res.id) {
      var target = cart.filter(function (i) { return i.name === product.name; })[0];
      if (target && !target.id) target.id = res.id;
    }
  });
}

document.querySelectorAll('.buy-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    addToCart({
      name: this.dataset.name,
      price: parseFloat(this.dataset.price),
      img: this.dataset.img
    });
  });
});

document.getElementById('cartItems').addEventListener('click', function (e) {
  var btn = e.target.closest('button[data-action]');
  if (!btn) return;
  var idx = parseInt(btn.dataset.idx, 10);
  var action = btn.dataset.action;
  var item = cart[idx];

  if (action === 'inc') {
    item.qty += 1;
    syncCartSetQty(item.id, item.qty);
  } else if (action === 'dec') {
    item.qty -= 1;
    if (item.qty <= 0) {
      cart.splice(idx, 1);
      syncCartSetQty(item.id, 0);
    } else {
      syncCartSetQty(item.id, item.qty);
    }
  } else if (action === 'remove') {
    cart.splice(idx, 1);
    syncCartRemove(item.id);
  }
  renderCart();
  updateCartCount();
});

document.getElementById('cartBtn').addEventListener('click', openCart);
document.getElementById('cartClose').addEventListener('click', closeCart);
document.getElementById('cartOverlay').addEventListener('click', function (e) {
  if (e.target === this) closeCart();
});
document.getElementById('checkoutBtn').addEventListener('click', function () {
  if (cart.length === 0) {
    showToast('Your cart is empty.');
    return;
  }
  if (!HOMI_LOGGED_IN) {
    showToast('Please sign in to check out.');
    window.location.href = 'login.php';
    return;
  }
  fetch('checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ cart: cart, csrf_token: HOMI_CSRF })
  })
    .then(function (res) { return res.json(); })
    .then(function (json) {
      if (json && json.ok) {
        showToast('Order placed! Thank you for shopping with HOMI.');
        cart = [];
        renderCart();
        updateCartCount();
        closeCart();
      } else {
        showToast((json && json.errors && json.errors[0]) || 'Could not place your order.');
        if (json && json.needsLogin) window.location.href = 'login.php';
      }
    })
    .catch(function () {
      showToast('Could not reach the server. Please try again.');
    });
});

renderCart();
loadServerCart();

/* ---------- Search ---------- */
var searchBar = document.getElementById('searchBar');
var searchInput = document.getElementById('searchInput');

function openSearch() {
  searchBar.classList.add('open');
  setTimeout(function () { searchInput.focus(); }, 220);
}
function closeSearch() {
  searchBar.classList.remove('open');
  searchInput.value = '';
  filterCards('');
}
document.getElementById('searchBtn').addEventListener('click', function () {
  if (searchBar.classList.contains('open')) closeSearch(); else openSearch();
});
document.getElementById('searchClose').addEventListener('click', closeSearch);
searchInput.addEventListener('input', function () { filterCards(this.value); });
searchInput.addEventListener('keydown', function (e) {
  if (e.key === 'Enter') {
    document.getElementById('collections').scrollIntoView({ behavior: 'smooth' });
  }
});

function filterCards(rawQuery) {
  var query = rawQuery.trim().toLowerCase();
  var anyMatch = false;

  document.querySelectorAll('.coll-card').forEach(function (card) {
    var name = card.querySelector('h4').textContent.toLowerCase();
    var match = query === '' || name.indexOf(query) !== -1;
    card.style.display = match ? '' : 'none';
    if (match) anyMatch = true;
  });

  document.querySelectorAll('.product-card').forEach(function (card) {
    var name = card.querySelector('.product-body h4').textContent.toLowerCase();
    var match = query === '' || name.indexOf(query) !== -1;
    card.style.display = match ? '' : 'none';
    if (match) anyMatch = true;
  });

  if (query !== '' && !anyMatch) {
    showToast('No results for "' + rawQuery.trim() + '"');
  }
}

/* ---------- Mobile nav ---------- */
var mobileNav = document.getElementById('mobileNav');
document.getElementById('menuToggle').addEventListener('click', function () {
  mobileNav.classList.toggle('open');
});
function closeMobileNav() { mobileNav.classList.remove('open'); }
mobileNav.querySelectorAll('a').forEach(function (a) {
  a.addEventListener('click', closeMobileNav);
});

/* ---------- Escape key closes whatever is open ---------- */
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    openModalIds.slice().forEach(function (id) { closeModal(id); });
    if (cartOpen) closeCart();
    closeSearch();
    closeMobileNav();
  }
});

/* ---------- Hero slide dots + image swap (matches the 01/02/03 indicator in the mock-up) ---------- */
var heroImages = [
  'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?q=80&w=1200&auto=format&fit=crop',
  'https://images.unsplash.com/photo-1616137466211-f939a420be84?q=80&w=1200&auto=format&fit=crop',
  'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?q=80&w=1200&auto=format&fit=crop'
];
var dotsWrap = document.getElementById('heroDots');
var dotCount = heroImages.length;
for (var i = 0; i < dotCount; i++) {
  var d = document.createElement('div');
  d.className = 'hero-num' + (i === 0 ? ' active' : '');
  d.dataset.index = i;
  d.textContent = '0' + (i + 1);
  dotsWrap.appendChild(d);
}
var dots = dotsWrap.querySelectorAll('.hero-num');
var heroActive = 0;
var heroImageEl = document.getElementById('heroImage');

function setActiveDot(idx) {
  dots.forEach(function (dot, i) { dot.classList.toggle('active', i === idx); });
  heroActive = idx;
  if (heroImageEl && heroImages[idx]) {
    heroImageEl.style.opacity = 0;
    setTimeout(function () {
      heroImageEl.src = heroImages[idx];
      heroImageEl.style.opacity = 1;
    }, 180);
  }
}
dots.forEach(function (dot) {
  dot.addEventListener('click', function () { setActiveDot(parseInt(this.dataset.index, 10)); });
});
setInterval(function () { setActiveDot((heroActive + 1) % dotCount); }, 4500);

/* ---------- Testimonials carousel ---------- */
var reviews = [
  {
    quote: "HOMI Furniture completely changed the look and comfort of our living room. The quality is excellent and worth every penny.",
    name: "Maria S. Graciaes",
    loc: "Quezon City",
    avatar: "https://randomuser.me/api/portraits/women/68.jpg"
  },
  {
    quote: "Simple, elegant, and very comfortable. It fits perfectly in our home. Highly recommended!",
    name: "John D. Douglas",
    loc: "New York",
    avatar: "https://randomuser.me/api/portraits/men/32.jpg"
  },
  {
    quote: "The design and finish are top-notch. We love our new dining set from HOMI!",
    name: "Angel P. Lazarro",
    loc: "Davao City",
    avatar: "https://randomuser.me/api/portraits/women/44.jpg"
  },
  {
    quote: "Delivery was fast and the assembly team was courteous. Our bedroom finally feels complete.",
    name: "Ramon T. Cruz",
    loc: "Cebu City",
    avatar: "https://randomuser.me/api/portraits/men/76.jpg"
  }
];
var revIndex = 0;
var track = document.getElementById('reviewTrack');

function renderReviews() {
  track.innerHTML = '';
  for (var i = 0; i < 3; i++) {
    var r = reviews[(revIndex + i) % reviews.length];
    var card = document.createElement('div');
    card.className = 'review';
    card.innerHTML =
      '<div class="stars">★★★★★</div>' +
      '<p>"' + r.quote + '"</p>' +
      '<div class="reviewer">' +
        '<img src="' + r.avatar + '" alt="' + r.name + '">' +
        '<div style="text-align:left;">' +
          '<div class="name">' + r.name + '</div>' +
          '<div class="loc">' + r.loc + '</div>' +
        '</div>' +
      '</div>';
    track.appendChild(card);
  }
}
document.getElementById('revPrev').addEventListener('click', function () {
  revIndex = (revIndex - 1 + reviews.length) % reviews.length;
  renderReviews();
});
document.getElementById('revNext').addEventListener('click', function () {
  revIndex = (revIndex + 1) % reviews.length;
  renderReviews();
});
renderReviews();

function syncCartAdd(item) {
  if (!HOMI_LOGGED_IN) return Promise.resolve(null);
  return fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'add', name: item.name, price: item.price, img: item.img, csrf_token: HOMI_CSRF })
  }).then(function (r) { return r.json(); }).catch(function () { return null; });
}
function syncCartSetQty(id, qty) {
  if (!HOMI_LOGGED_IN || !id) return;
  fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'set_qty', id: id, qty: qty, csrf_token: HOMI_CSRF })
  });
}
function syncCartRemove(id) {
  if (!HOMI_LOGGED_IN || !id) return;
  fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'remove', id: id, csrf_token: HOMI_CSRF })
  });
}
function loadServerCart() {
  if (!HOMI_LOGGED_IN) return;
  fetch('cart.php')
    .then(function (r) { return r.json(); })
    .then(function (json) {
      if (json && json.ok) {
        cart = json.items;
        renderCart();
        updateCartCount();
      }
    })
    .catch(function () {});
}