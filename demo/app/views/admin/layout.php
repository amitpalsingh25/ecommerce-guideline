<?php
/** @var string $content */
$title = $title ?? 'Admin · ' . SITE_NAME;
$cur = current_path();
$links = [
    ['/admin', 'Dashboard', 'gem'],
    ['/admin/hero', 'Hero slideshow', 'image'],
    ['/admin/products', 'Products', 'extinguisher'],
    ['/admin/categories', 'Categories', 'cabinet'],
    ['/admin/media', 'Media', 'image'],
    ['/admin/blog', 'Blog', 'sign'],
    ['/admin/enquiries', 'Enquiries', 'mail'],
    ['/admin/orders', 'Orders', 'cart'],
    ['/admin/emails', 'Emails', 'mail'],
    ['/admin/logs', 'Event Logs', 'clock'],
    ['/admin/settings', 'Settings', 'valve'],
];
?><!DOCTYPE html>
<html lang="en-AU">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?= theme_style() ?>
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="<?= url('admin') ?>" class="admin-brand"><?= icon('flame', 'wm-flame') ?>FireSafe Admin</a>
    <nav class="admin-nav">
      <?php foreach ($links as $l):
        $active = $l[0] === '/admin' ? ($cur === '/admin') : str_starts_with($cur, $l[0]); ?>
        <a href="<?= url(ltrim($l[0], '/')) ?>" class="<?= $active ? 'active' : '' ?>"><?= icon($l[2]) ?><?= e($l[1]) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-foot admin-nav">
      <a href="<?= url('') ?>" target="_blank"><?= icon('arrow-right') ?>View site</a>
      <a href="<?= url('admin/logout') ?>"><?= icon('minus') ?>Sign out</a>
    </div>
  </aside>
  <div class="admin-main"><?= $content ?></div>
</div>
<script type="module" src="<?= url('assets/js/richtext.js') ?>"></script>
<script>window.FSA={mediaList:<?= json_encode(url('admin/media/list')) ?>,mediaDelete:<?= json_encode(url('admin/media/delete')) ?>,mediaInfo:<?= json_encode(url('admin/media/info')) ?>,mediaSave:<?= json_encode(url('admin/media/save')) ?>,mediaEdit:<?= json_encode(url('admin/media/edit')) ?>,upload:<?= json_encode(url('admin/upload')) ?>,csrf:<?= json_encode(csrf_token()) ?>};</script>
<script src="<?= url('assets/js/media.js') ?>"></script>
</body>
</html>
