<?php
function headerView(string $page): void { 
    $count = 0; 
    foreach (cartRows() as $r) {
        $count += $r['qty']; 
    }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="DOOM REVERSAL — koleksi hoodie dan everyday essentials. Proyek PHP lokal.">
    <title>DOOM REVERSAL — <?= e(ucfirst($page)) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
<header class="header">
    <a class="brand" href="index.php" aria-label="DOOM REVERSAL beranda">
        <img src="assets/images/logo.png" alt="DOOM REVERSAL">
    </a>
    <nav aria-label="Koleksi utama">
        <?php foreach (['Men', 'Women', 'Pants', 'Kids'] as $cat): ?>
            <a class="<?= ($_GET['category'] ?? '') === $cat ? 'active' : '' ?>" href="index.php?page=catalog&amp;category=<?= $cat ?>"><?= $cat ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="tools">
        <a href="index.php?page=catalog&amp;search=1" aria-label="Cari produk"><?= icon('search') ?></a>
        <a href="index.php?page=<?= isset($_SESSION['user']) ? 'account' : 'login' ?>" aria-label="Akun"><?= icon('user') ?></a>
        <a href="index.php?page=cart" aria-label="Keranjang, <?= $count ?> barang">
            <?= icon('cart') ?>
            <?php if ($count): ?>
                <span class="badge"><?= $count ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>

<?php if (!empty($_SESSION['flash'])): ?>
    <div role="status" class="notice"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php 
}

function footerView(): void { 
?>
<footer class="footer">
    <div>
        <p>“When the end feels near, turn it around and start again.”</p>
        <a class="footer-link" href="index.php?page=catalog">Explore the collection <?= icon('arrow') ?></a>
    </div>
    <div class="footer-bottom">
        <img src="assets/images/logo.png" alt="DOOM REVERSAL">
        <p>© <?= date('Y') ?> DOOM REVERSAL. All rights reserved.</p>
        <span>Proyek pembelajaran · Checkout simulasi</span>
    </div>
</footer>
</body>
</html>
<?php 
}

function card(array $p): void { 
?>
<article class="card">
    <a href="index.php?page=product&amp;id=<?= $p['id'] ?>">
        <div class="card-image">
            <img loading="lazy" src="assets/images/<?= e($p['image']) ?>" alt="<?= e($p['name'] . ' — ' . $p['color']) ?>">
            <span>VIEW PRODUCT <?= icon('arrow') ?></span>
        </div>
        <div class="card-info">
            <h3><?= e($p['name']) ?></h3>
            <p class="price"><?= rupiah($p['price']) ?></p>
            <small><?= e($p['category'] . ' / ' . $p['color']) ?></small>
            <div class="swatches" aria-label="Warna produk">
                <i style="background:<?= e(['Lilac' => '#9396ba', 'Stone' => '#716b61', 'Burgundy' => '#582a35', 'Black' => '#222', 'Navy' => '#314756', 'Olive' => '#7b8260', 'Forest' => '#233a36', 'Sand' => '#b6b49e', 'Pink' => '#e38cc0'][$p['color']] ?? '#222') ?>"></i>
            </div>
        </div>
    </a>
</article>
<?php 
} 
?>