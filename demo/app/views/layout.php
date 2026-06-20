<?php
/** @var string $content */
$title = $title ?? (SITE_NAME . ' — Fire Protection Equipment, Australia-wide');
$desc = $desc ?? 'Extinguishers, hose reels, hydrants, signage and fittings. Compliant fire safety equipment, dispatched across Australia.';
$brand = branding();
$tog = toggles();
$navLinks = [
    ['Home', url('')], ['Products', url('products')], ['Categories', url('categories')],
    ['Blog', url('blog')], ['About', url('about')], ['Contact us', url('contact')],
];
$tops = top_categories();
?><!DOCTYPE html>
<html lang="en-AU">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="icon" href="<?= e($brand['faviconUrl']) ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?= theme_style() ?>
<script>window.FSA = { sellingEnabled: <?= $tog['sellingEnabled'] ? 'true' : 'false' ?>, base: "<?= e(BASE_PATH) ?>" };</script>
</head>
<body>
  <div class="topbar"><div class="wrap">
    <div class="tb-left"><span class="dot"></span> Australian owned &amp; operated — fire safety specialists</div>
    <div class="tb-right">
      <a href="tel:<?= e(CO_PHONE_RAW) ?>"><?= icon('phone') ?><?= e(CO_PHONE) ?></a>
      <a href="mailto:<?= e(CO_EMAIL) ?>"><?= icon('mail') ?><?= e(CO_EMAIL) ?></a>
    </div>
  </div></div>

  <header class="site-header"><div class="wrap">
    <a class="brand" href="<?= url('') ?>" aria-label="<?= e(SITE_NAME) ?> home">
      <img src="<?= e($brand['logoUrl']) ?>" alt="<?= e(SITE_NAME) ?>" style="<?= e(logo_style($brand)) ?>">
    </a>
    <nav class="nav-links" id="nav">
      <?php foreach ($navLinks as $l): ?><a href="<?= e($l[1]) ?>"><?= e($l[0]) ?></a><?php endforeach; ?>
    </nav>
    <div class="header-actions">
      <a href="<?= url('products') ?>" class="icon-btn hide-sm" aria-label="Search"><?= icon('search') ?></a>
      <a href="<?= url(current_customer() ? 'account' : 'login') ?>" class="icon-btn hide-sm" aria-label="<?= current_customer() ? 'My account' : 'Log in' ?>"><?= icon('user') ?></a>
      <button class="icon-btn" data-cart-open aria-label="Open cart"><?= icon('cart') ?><span class="cart-count">0</span></button>
      <?php $catUrl = $brand['catalogueUrl'] ?? ''; ?>
      <a href="<?= $catUrl ? e($catUrl) : url('contact') ?>"<?= $catUrl ? ' target="_blank" download' : '' ?> class="btn btn-ghost" style="margin-left:6px">Catalogue</a>
      <a href="<?= url('contact') ?>" class="btn btn-flame btn-pulse" style="margin-left:6px">Get a Free Quote</a>
      <button class="icon-btn menu-toggle" id="menuBtn" aria-label="Menu"><?= icon('menu') ?></button>
    </div>
  </div></header>

  <?php if ($tops): ?>
  <div class="cat-bar"><div class="wrap"><nav class="cat-bar-nav" aria-label="Product categories">
    <a href="<?= url('products') ?>" class="cat-bar-all"><?= icon('menu', 'icon', 'width:15px;height:15px') ?>All Products</a>
    <?php foreach ($tops as $c): ?><a href="<?= url('category/' . $c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
  </nav></div></div>
  <?php endif; ?>

  <main><?= $content ?></main>

  <footer class="site-footer"><div class="wrap">
    <div class="foot-top">
      <div>
        <div class="wordmark"><?= icon('flame', 'wm-flame') ?>Fire<span class="s">Safe</span>Australia</div>
        <p class="foot-tag">Fire protection and essential-services equipment, dispatched right across Australia.</p>
      </div>
      <div class="foot-col"><h5>Shop</h5>
        <?php foreach ($tops as $c): ?><a href="<?= url('category/' . $c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
      </div>
      <div class="foot-col"><h5>Company</h5>
        <a href="<?= url('about') ?>">About us</a><a href="<?= url('blog') ?>">Blog</a>
        <a href="<?= url('contact') ?>">Contact</a><a href="<?= url('admin') ?>">Admin</a>
      </div>
      <div class="foot-col foot-contact"><h5>Get in touch</h5>
        <p><?= icon('pin') ?><?= e(CO_ADDRESS) ?></p>
        <p><?= icon('phone') ?><?= e(CO_PHONE) ?></p>
        <p><?= icon('mail') ?><?= e(CO_EMAIL) ?></p>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
      <span class="fb-r">Compliant fire safety, sorted.</span>
    </div>
  </div></footer>

  <!-- Cart drawer -->
  <div class="cart-overlay" id="cart-overlay" data-cart-close></div>
  <aside class="cart-drawer" id="cart-drawer" role="dialog" aria-label="Cart">
    <div class="cart-drawer-head">
      <h3><?= $tog['sellingEnabled'] ? 'Your cart' : 'Your enquiry' ?></h3>
      <button class="icon-btn" data-cart-close aria-label="Close cart"><?= icon('plus', 'icon', 'transform:rotate(45deg)') ?></button>
    </div>
    <div class="cart-drawer-body" id="cart-drawer-body"></div>
    <div class="cart-drawer-foot" id="cart-drawer-foot"></div>
  </aside>

  <script>
    var mb = document.getElementById('menuBtn'), nav = document.getElementById('nav');
    if (mb) mb.addEventListener('click', function(){ nav.classList.toggle('open'); });
    document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('in'); });
  </script>
  <script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
