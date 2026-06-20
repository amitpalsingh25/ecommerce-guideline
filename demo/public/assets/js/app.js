/* Fire Safe Australia — cart + drawer + Zomato-style qty steppers (vanilla JS) */
(function () {
  "use strict";
  var KEY = "fsa-cart";
  var FSA = window.FSA || { sellingEnabled: false, base: "" };
  var selling = !!FSA.sellingEnabled;
  var U = function (p) { return (FSA.base || "") + p; };

  // ---- store ----
  function read() { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } }
  function write(items) { localStorage.setItem(KEY, JSON.stringify(items)); renderAll(); }
  function find(items, id) { for (var i = 0; i < items.length; i++) if (items[i].productId === id) return items[i]; return null; }
  function count() { return read().reduce(function (n, i) { return n + i.qty; }, 0); }
  function subtotal() { return read().reduce(function (s, i) { return s + (i.price || 0) * i.qty; }, 0); }
  function money(v) { return "$" + Number(v).toFixed(2); }

  function add(item) {
    var items = read(); var ex = find(items, item.productId);
    if (ex) ex.qty += 1; else items.push(Object.assign({ qty: 1 }, item));
    write(items); openDrawer();
  }
  function setQty(id, qty) {
    var items = read(); var ex = find(items, id);
    if (!ex) return;
    if (qty <= 0) items = items.filter(function (i) { return i.productId !== id; });
    else ex.qty = qty;
    write(items);
  }
  function remove(id) { write(read().filter(function (i) { return i.productId !== id; })); }
  function clear() { write([]); }

  // ---- control rendering (Add <-> stepper) ----
  function stepper(id, qty) {
    return '<div class="qty-stepper" role="group">' +
      '<button data-dec="' + id + '" aria-label="Decrease">' + (qty <= 1 ? icon("trash") : icon("minus")) + "</button>" +
      "<span>" + qty + "</span>" +
      '<button data-inc="' + id + '" aria-label="Increase">' + icon("plus") + "</button></div>";
  }
  function addBtn(cls, label) {
    return '<button class="' + cls + ' js-add-btn">' + label + " " + icon("plus") + "</button>";
  }
  function renderControls() {
    var items = read();
    document.querySelectorAll(".js-cart-control").forEach(function (el) {
      var data;
      try { data = JSON.parse(el.getAttribute("data-product")); } catch (e) { return; }
      var it = find(items, data.productId);
      var cls = el.getAttribute("data-btnclass") || "btn-sm";
      var label = el.getAttribute("data-label") || "Add";
      el.innerHTML = it ? stepper(data.productId, it.qty) : addBtn(cls, label);
      el._product = data;
    });
  }

  function renderBadge() {
    var c = count();
    document.querySelectorAll(".cart-count").forEach(function (b) { b.textContent = c; });
  }

  // ---- drawer ----
  function openDrawer() { document.body.classList.add("drawer-open"); var d = document.getElementById("cart-overlay"); var a = document.getElementById("cart-drawer"); if (d) d.classList.add("open"); if (a) a.classList.add("open"); }
  function closeDrawer() { document.body.classList.remove("drawer-open"); var d = document.getElementById("cart-overlay"); var a = document.getElementById("cart-drawer"); if (d) d.classList.remove("open"); if (a) a.classList.remove("open"); }

  function lineRow(i) {
    var media = i.image ? '<img src="' + i.image + '" alt="">' : icon("box");
    var price = (selling && i.price != null) ? money(i.price) : "Enquire for price";
    return '<div class="cart-line">' +
      '<a class="cart-line-media" href="' + U("/products/" + i.slug) + '">' + media + "</a>" +
      '<div style="flex:1;min-width:0">' +
        '<a href="' + U("/products/" + i.slug) + '" style="font-weight:600;color:var(--charcoal);font-size:14px;display:block">' + esc(i.title) + "</a>" +
        '<div class="prod-sku" style="margin:2px 0 8px">' + price + "</div>" +
        stepper(i.productId, i.qty) +
      "</div>" +
      '<button class="icon-btn" data-remove="' + i.productId + '" aria-label="Remove">' + icon("trash") + "</button>" +
    "</div>";
  }

  function renderDrawer() {
    var body = document.getElementById("cart-drawer-body");
    var foot = document.getElementById("cart-drawer-foot");
    if (!body) return;
    var items = read();
    if (!items.length) {
      body.innerHTML = '<div style="text-align:center;padding:48px 0;color:var(--muted)"><p>Your ' + (selling ? "cart" : "enquiry list") + " is empty.</p></div>";
      if (foot) foot.innerHTML = "";
      return;
    }
    body.innerHTML = items.map(lineRow).join("");
    if (foot) {
      var top = selling
        ? '<div style="display:flex;justify-content:space-between;margin-bottom:14px;font-weight:700"><span>Subtotal</span><span>' + money(subtotal()) + "</span></div>"
        : '<p style="margin:0 0 14px;font-size:13px;color:var(--muted)">We’ll reply with pricing after you send your enquiry.</p>';
      foot.innerHTML = top +
        '<div style="display:grid;gap:10px">' +
        '<a href="' + U("/checkout") + '" class="btn btn-flame" style="justify-content:center">' + (selling ? "Checkout" : "Get enquiry") + " " + icon("arrow") + "</a>" +
        '<a href="' + U("/cart") + '" class="btn btn-ghost" style="justify-content:center">View full ' + (selling ? "cart" : "list") + "</a></div>";
    }
  }

  // ---- cart + checkout pages ----
  function renderCartPage() {
    var el = document.getElementById("cart-page");
    if (!el) return;
    var items = read();
    if (!items.length) {
      el.innerHTML = '<div style="text-align:center;padding:40px 0"><p style="color:var(--muted);margin-bottom:20px">Your ' + (selling ? "cart" : "enquiry list") + ' is empty.</p><a href="' + U("/products") + '" class="btn btn-flame">Browse the catalogue ' + icon("arrow") + "</a></div>";
      return;
    }
    var rows = items.map(function (i) {
      var price = (selling && i.price != null) ? money(i.price) : "Enquire for price";
      var media = i.image ? '<img src="' + i.image + '" alt="">' : icon("box");
      return '<div style="display:flex;gap:16px;align-items:center;border:1px solid var(--border);border-radius:var(--radius);padding:16px;background:#fff">' +
        '<div class="cart-line-media">' + media + "</div>" +
        '<div style="flex:1;min-width:0"><div class="prod-sku">' + esc(i.sku) + '</div><a href="' + U("/products/" + i.slug) + '" style="font-weight:600;color:var(--charcoal)">' + esc(i.title) + '</a><div style="margin-top:4px;font-size:13px;color:var(--muted)">' + price + "</div></div>" +
        stepper(i.productId, i.qty) +
        '<button class="icon-btn" data-remove="' + i.productId + '" aria-label="Remove">' + icon("trash") + "</button></div>";
    });
    var footer = '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:20px;border-top:1px solid var(--border)">' +
      (selling ? '<div><div class="prod-sku">Subtotal</div><div style="font-family:var(--f-display);font-weight:800;font-size:24px">' + money(subtotal()) + "</div></div>"
               : '<div style="color:var(--muted);font-size:14px;max-width:360px">Pricing is provided on enquiry.</div>') +
      '<a href="' + U("/checkout") + '" class="btn btn-flame">' + (selling ? "Checkout" : "Get enquiry") + " " + icon("arrow") + "</a></div>";
    el.innerHTML = '<div style="display:grid;gap:16px">' + rows.join("") + footer + "</div>";
  }

  function renderCheckoutSummary() {
    var el = document.getElementById("checkout-items");
    if (!el) return;
    var items = read();
    document.querySelectorAll(".js-cart-json").forEach(function (inp) { inp.value = JSON.stringify(items); });
    if (!items.length) { el.innerHTML = '<p style="color:var(--muted)">Your list is empty. <a href="' + U("/products") + '" style="color:var(--flame-deep)">Browse products</a>.</p>'; var f = document.getElementById("checkout-form"); if (f) f.style.display = "none"; return; }
    var rows = items.map(function (i) {
      var price = (selling && i.price != null) ? money(i.price * i.qty) : "Enquire";
      return '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)"><span style="font-size:14px">' + esc(i.title) + ' <span class="prod-sku">× ' + i.qty + '</span></span><span style="font-size:14px;color:var(--muted)">' + price + "</span></div>";
    });
    el.innerHTML = rows.join("");
  }

  function renderAll() { renderControls(); renderBadge(); renderDrawer(); renderCartPage(); renderCheckoutSummary(); }

  // ---- icons (inline svg) ----
  function icon(name) {
    var p = {
      plus: '<path d="M12 5v14M5 12h14"/>', minus: '<path d="M5 12h14"/>',
      trash: '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
      arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>', box: '<rect x="8" y="7" width="8" height="14" rx="3"/>'
    }[name] || "";
    return '<svg class="icon" viewBox="0 0 24 24">' + p + "</svg>";
  }
  function esc(s) { var d = document.createElement("div"); d.textContent = s == null ? "" : s; return d.innerHTML; }

  // ---- events ----
  document.addEventListener("click", function (ev) {
    var t = ev.target.closest("[data-inc],[data-dec],[data-remove],.js-add-btn,[data-cart-open],[data-cart-close]");
    if (!t) return;
    if (t.hasAttribute("data-cart-open")) { ev.preventDefault(); openDrawer(); return; }
    if (t.hasAttribute("data-cart-close")) { ev.preventDefault(); closeDrawer(); return; }
    var items = read();
    if (t.classList.contains("js-add-btn")) {
      var holder = t.closest(".js-cart-control");
      if (holder && holder._product) add(holder._product);
      return;
    }
    var inc = t.getAttribute("data-inc"); if (inc) { var a = find(items, inc); setQty(inc, (a ? a.qty : 0) + 1); return; }
    var dec = t.getAttribute("data-dec"); if (dec) { var b = find(items, dec); setQty(dec, (b ? b.qty : 1) - 1); return; }
    var rm = t.getAttribute("data-remove"); if (rm) { remove(rm); return; }
  });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeDrawer(); });

  // expose minimal API + clear-on-success
  window.FSACart = { clear: clear, count: count };

  // ---- hero slideshow ----
  function initHero() {
    var el = document.querySelector(".hero-slider");
    if (!el) return;
    var slides = Array.prototype.slice.call(el.querySelectorAll(".hero-slide"));
    if (slides.length < 1) return;
    var mode = el.getAttribute("data-transition") || "fade";
    var speed = parseInt(el.getAttribute("data-speed"), 10) || 600;
    var interval = (parseInt(el.getAttribute("data-interval"), 10) || 0) * 1000;
    el.style.setProperty("--hero-speed", speed + "ms");
    var cur = 0, timer = null;

    function layout() {
      slides.forEach(function (s, i) {
        if (mode === "slide") { s.style.transform = "translateX(" + ((i - cur) * 100) + "%)"; s.style.opacity = "1"; s.classList.toggle("active", i === cur); }
        else { s.classList.toggle("active", i === cur); }
      });
      el.querySelectorAll(".hero-dot").forEach(function (d, i) { d.classList.toggle("active", i === cur); });
    }
    function go(n) { cur = (n + slides.length) % slides.length; layout(); }
    function next() { go(cur + 1); }
    function start() { if (interval > 0 && slides.length > 1) timer = setInterval(next, interval); }
    function stop() { if (timer) clearInterval(timer); timer = null; }

    el.querySelectorAll("[data-go]").forEach(function (b) { b.addEventListener("click", function () { go(+b.getAttribute("data-go")); stop(); start(); }); });
    var p = el.querySelector("[data-prev]"), n = el.querySelector("[data-next]");
    if (p) p.addEventListener("click", function () { go(cur - 1); stop(); start(); });
    if (n) n.addEventListener("click", function () { next(); stop(); start(); });
    el.addEventListener("mouseenter", stop); el.addEventListener("mouseleave", start);
    if (mode === "slide") slides.forEach(function (s) { s.classList.add("active"); }); // keep all visible, transform-positioned
    layout(); start();
  }

  function initFeatured() {
    document.querySelectorAll(".feat-carousel").forEach(function (c) {
      var track = c.querySelector(".feat-track");
      if (!track) return;
      var prev = c.querySelector(".feat-arrow.prev"), next = c.querySelector(".feat-arrow.next");
      function step() { return Math.max(200, track.clientWidth * 0.85); }
      function atEnd() { return track.scrollLeft + track.clientWidth >= track.scrollWidth - 5; }
      if (prev) prev.addEventListener("click", function () { track.scrollBy({ left: -step(), behavior: "smooth" }); });
      if (next) next.addEventListener("click", function () { track.scrollBy({ left: step(), behavior: "smooth" }); });
      var ap = parseInt(c.getAttribute("data-autoplay"), 10) || 0;
      if (ap > 0) {
        var tick = function () { if (atEnd()) track.scrollTo({ left: 0, behavior: "smooth" }); else track.scrollBy({ left: step(), behavior: "smooth" }); };
        var timer = setInterval(tick, ap * 1000);
        c.addEventListener("mouseenter", function () { clearInterval(timer); });
        c.addEventListener("mouseleave", function () { timer = setInterval(tick, ap * 1000); });
      }
    });
  }

  function initSwatches() {
    document.querySelectorAll(".pc-swatches").forEach(function (row) {
      var card = row.closest(".prod-card");
      if (!card) return;
      var img = card.querySelector(".pc-img");
      if (!img) return;
      var first = row.getAttribute("data-first");
      var swatches = row.querySelectorAll(".pc-swatch");
      swatches.forEach(function (u) { var p = new Image(); p.src = u.getAttribute("data-img"); }); // preload
      function setImg(s) {
        img.src = s.getAttribute("data-img");
        img.classList.remove("pc-slide");
        void img.offsetWidth; // restart animation
        img.classList.add("pc-slide");
        swatches.forEach(function (x) { x.classList.toggle("active", x === s); });
      }
      swatches.forEach(function (s) {
        s.addEventListener("click", function (e) { e.preventDefault(); setImg(s); });
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () { renderAll(); initHero(); initFeatured(); initSwatches(); });
  renderAll();
})();
