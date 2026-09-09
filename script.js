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

/* ---------- Signed-in greeting on the homepage (localStorage) ---------- */
/* The static homepage (index.html) shows "HI, NAME · LOG OUT" when the
   visitor signed in through login.html. */
(function () {
  var accountLink = document.getElementById('accountLink');
  if (!accountLink) return;

  var userName = null;
  try { userName = window.localStorage.getItem('homi_user'); } catch (e) { return; }
  if (!userName) return;

  var chip = document.createElement('div');
  chip.className = 'user-chip';
  chip.title = 'Signed in as ' + userName;

  var hi = document.createElement('span');
  hi.className = 'user-hi';
  hi.textContent = 'HI, ' + userName.split(' ')[0].toUpperCase();

  var out = document.createElement('a');
  out.href = '#';
  out.className = 'user-logout';
  out.textContent = 'LOG OUT';
  out.addEventListener('click', function (e) {
    e.preventDefault();
    try { window.localStorage.removeItem('homi_user'); } catch (err) {}
    window.location.reload();
  });

  chip.appendChild(hi);
  chip.appendChild(out);
  accountLink.parentNode.replaceChild(chip, accountLink);
})();

/* ---------- Quote form ---------- */
document.getElementById('quoteForm').addEventListener('submit', function (e) {
  e.preventDefault();
  if (!this.checkValidity()) { this.reportValidity(); return; }
  showToast("Thanks! Your quote request has been sent — we'll reach out within 24 hours.");
  this.reset();
  closeModal('quoteModal');
});

/* ---------- Contact form ---------- */
document.getElementById('contactForm').addEventListener('submit', function (e) {
  e.preventDefault();
  if (!this.checkValidity()) { this.reportValidity(); return; }
  showToast('Message sent! Our team will get back to you shortly.');
  this.reset();
  closeModal('contactModal');
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
  if (action === 'inc') {
    cart[idx].qty += 1;
  } else if (action === 'dec') {
    cart[idx].qty -= 1;
    if (cart[idx].qty <= 0) cart.splice(idx, 1);
  } else if (action === 'remove') {
    cart.splice(idx, 1);
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
  showToast('Order placed! Thank you for shopping with HOMI.');
  cart = [];
  renderCart();
  updateCartCount();
  closeCart();
});

renderCart();

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
