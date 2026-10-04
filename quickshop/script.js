const CATS = [['Mobiles & Accessories','📱'],['Electronics','🎧'],["Men's Fashion",'👕'],["Women's Fashion",'👗'],['Home & Kitchen','🏠'],['Beauty & Care','💄'],['Grocery','🛒'],['Sports & Fitness','🏋️']];
const GR = ['#e0e7ff,#c7d2fe','#fef3c7,#fde68a','#dcfce7,#bbf7d0','#fce7f3,#fbcfe8','#e0f2fe,#bae6fd','#ffedd5,#fed7aa'];
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const inr = n => '₹' + Number(n).toLocaleString('en-IN', {maximumFractionDigits: 2});
const page = document.body.dataset.page;
let products = [], cart = [];
try { cart = JSON.parse(localStorage.getItem('qs_cart2')) || []; } catch (e) {}
const disc = p => (+p.mrp > +p.price ? Math.round((1 - p.price / p.mrp) * 100) : 0);

function layout() {
    const q = new URLSearchParams(location.search).get('q') || '';
    document.getElementById('site-header').innerHTML = `<header><div class="hbar">
        <a class="logo" href="index.html">Quick<span>Shop</span></a>
        <form class="search" action="shop.html"><input name="q" placeholder="Search for products, categories and more" value="${esc(q)}"><button>Search</button></form>
        <nav><a href="index.html" class="${page=='home'?'active':''}">Home</a><a href="shop.html" class="${page=='shop'?'active':''}">Shop</a>
        <span id="auth"><a href="login.php">Login</a><a href="register.php">Register</a></span>
        <div class="cart-icon" onclick="toggleCart()">🛒 Cart (<span id="cart-count">0</span>)</div></nav></div></header>`;
    document.getElementById('site-footer').innerHTML = `<footer><div class="fgrid">
        <div><h4>QuickShop</h4><p style="font-size:.9rem;line-height:1.6">Your one-stop online store for mobiles, fashion, home, beauty, grocery and sports essentials.</p></div>
        <div><h4>Categories</h4>${CATS.slice(0,5).map(c=>`<a href="shop.html?cat=${encodeURIComponent(c[0])}">${esc(c[0])}</a>`).join('')}</div>
        <div><h4>My Account</h4><a href="login.php">Login</a><a href="register.php">Register</a><a href="shop.html">All Products</a></div>
        </div><div class="copy">© ${new Date().getFullYear()} QuickShop. All prices in Indian Rupees (₹).</div></footer>`;
    document.getElementById('cart-root').innerHTML = `<div id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header"><h2>Your Cart</h2><button onclick="toggleCart()">✕</button></div>
        <div id="cart-items"></div>
        <div class="cart-footer"><h3>Total: <span id="cart-total">₹0</span></h3>
        <form action="checkout.php" method="POST"><input type="hidden" name="cart_data" id="cart-form-input">
        <button type="submit" class="checkout-btn">Proceed to Checkout</button></form></div></div>
        <div id="overlay" onclick="toggleCart()"></div>`;
}

function card(p) {
    const i = Math.max(0, CATS.findIndex(c => c[0] == p.category)), d = disc(p);
    const emoji = p.image.startsWith('emoji:');
    // Photo priority: uploaded image, else images/<CODE>.jpg, else emoji tile
    const src = emoji ? `images/${p.code}.jpg` : p.image;
    const img = `<img src="${esc(src)}" alt="${esc(p.name)}" loading="lazy" data-g="${GR[i % 6]}" data-e="${emoji ? p.image.slice(6) : '🛍️'}" onerror="imgFail(this)">`;
    return `<div class="card">${img}<h3>${esc(p.name)}</h3><div><span class="rate">${(+p.rating).toFixed(1)} ★</span></div>
        <div class="pr"><b>${inr(p.price)}</b>${d ? `<s>${inr(p.mrp)}</s><em>${d}% off</em>` : ''}</div>
        <button class="add-btn" data-id="${p.id}">Add to Cart</button></div>`;
}

function renderHome() {
    document.getElementById('cat-grid').innerHTML = CATS.map(c => `<a class="cat" href="shop.html?cat=${encodeURIComponent(c[0])}"><span>${c[1]}</span>${esc(c[0])}</a>`).join('');
    document.getElementById('deals').innerHTML = [...products].sort((a, b) => disc(b) - disc(a)).slice(0, 8).map(card).join('');
    document.getElementById('top').innerHTML = [...products].sort((a, b) => b.rating - a.rating).slice(0, 8).map(card).join('');
}

function renderShop() {
    const q = new URLSearchParams(location.search), cat = q.get('cat') || '', s = (q.get('q') || '').toLowerCase();
    const cats = [...new Set([...CATS.map(c => c[0]), ...products.map(p => p.category)])];
    document.getElementById('side').innerHTML = `<h4>Categories</h4><a href="shop.html" class="${cat?'':'on'}">All Products <small>${products.length}</small></a>` +
        cats.map(c => `<a href="shop.html?cat=${encodeURIComponent(c)}" class="${c==cat?'on':''}">${esc(c)} <small>${products.filter(p=>p.category==c).length}</small></a>`).join('');
    let list = products.filter(p => (!cat || p.category == cat) && (!s || (p.name + ' ' + p.category).toLowerCase().includes(s)));
    const sort = document.getElementById('sort').value;
    if (sort == 'low') list.sort((a, b) => a.price - b.price);
    if (sort == 'high') list.sort((a, b) => b.price - a.price);
    if (sort == 'rate') list.sort((a, b) => b.rating - a.rating);
    if (sort == 'disc') list.sort((a, b) => disc(b) - disc(a));
    document.getElementById('shop-title').textContent = s ? `Results for "${q.get('q')}"` : (cat || 'All Products');
    document.getElementById('shop-count').textContent = list.length + ' products';
    document.getElementById('products').innerHTML = list.length ? list.map(card).join('') : '<p style="padding:40px">No products found. Try another search or category.</p>';
}

function toggleCart() {
    document.getElementById('cart-sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('open');
}
function addToCart(id, name, price) {
    const e = cart.find(i => i.id === id);
    if (e) e.quantity++; else cart.push({ id, name, price, quantity: 1 });
    updateCartUI();
}
function changeQty(id, d) {
    const it = cart.find(i => i.id === id);
    if (!it) return;
    it.quantity += d;
    if (it.quantity <= 0) cart = cart.filter(i => i.id !== id);
    updateCartUI();
}
function updateCartUI() {
    localStorage.setItem('qs_cart2', JSON.stringify(cart));
    document.getElementById('cart-count').textContent = cart.reduce((s, i) => s + i.quantity, 0);
    const box = document.getElementById('cart-items');
    let total = 0;
    box.innerHTML = cart.length ? '' : '<p style="color:#64748b;text-align:center;margin-top:40px">Your cart is empty</p>';
    cart.forEach(i => {
        total += i.price * i.quantity;
        box.innerHTML += `<div class="cart-item"><div><h4>${esc(i.name)}</h4><small>${inr(i.price)} × ${i.quantity}</small>
            <div class="qty"><button onclick="changeQty('${esc(i.id)}',-1)">−</button><button onclick="changeQty('${esc(i.id)}',1)">+</button></div></div>
            <span>${inr(i.price * i.quantity)}</span></div>`;
    });
    document.getElementById('cart-total').textContent = inr(total);
    document.getElementById('cart-form-input').value = JSON.stringify(cart.map(i => ({ id: i.id, quantity: i.quantity })));
}

document.addEventListener('click', e => {
    const b = e.target.closest('.add-btn[data-id]');
    if (!b) return;
    const p = products.find(x => String(x.id) == b.dataset.id);
    if (p) { addToCart(String(p.id), p.name, +p.price); b.textContent = '✓ Added'; setTimeout(() => b.textContent = 'Add to Cart', 900); }
});

document.addEventListener('DOMContentLoaded', () => {
    layout(); updateCartUI();
    fetch('api/session.php').then(r => r.json()).then(s => {
        if (s.loggedIn) document.getElementById('auth').innerHTML = `<span>Hi, ${esc(s.username)}</span>${s.isAdmin ? '<a href="admin.php">Admin</a>' : ''}<a href="logout.php">Logout</a>`;
    }).catch(() => {});
    fetch('api/get_products.php').then(r => r.json()).then(p => {
        products = p;
        const valid = new Set(p.map(x => String(x.id)));
        cart = cart.filter(i => valid.has(String(i.id))); updateCartUI();
        page == 'home' ? renderHome() : renderShop();
    }).catch(() => {
        const t = document.getElementById('products') || document.getElementById('deals');
        if (t) t.innerHTML = '<p style="padding:40px">Could not load products. Run install.php first.</p>';
    });
});

function imgFail(el) {
    el.onerror = null;
    el.outerHTML = `<div class="ph" style="background:linear-gradient(135deg,${el.dataset.g})">${el.dataset.e}</div>`;
}
