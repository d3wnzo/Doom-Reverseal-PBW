<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/actions.php';
require __DIR__ . '/includes/layout.php';

$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
$allowed = ['home', 'catalog', 'product', 'cart', 'checkout', 'login', 'register', 'account', 'help', 'report', 'payment'];

if (!in_array($page, $allowed, true)) {
    http_response_code(404);
    $page = '404';
}

if (in_array($page, ['account', 'checkout', 'payment'], true) && empty($_SESSION['user'])) {
    flash('Login dulu untuk melanjutkan.');
    go('index.php?page=login');
}

$p = $page === 'product' ? ($products[(int)($_GET['id'] ?? 0)] ?? null) : null;

if ($page === 'product' && !$p) {
    http_response_code(404);
    $page = '404';
}

$paymentOrder = null;
if ($page === 'payment') {
    $statement = db()->prepare('SELECT o.*, p.method, p.status AS payment_status, p.reference, p.paid_at FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.id=? AND o.user_id=?');
    $statement->execute([(int)($_GET['order'] ?? 0), $_SESSION['user']['id']]);
    $paymentOrder = $statement->fetch();
    if (!$paymentOrder) {
        http_response_code(404);
        $page = '404';
    }
}

headerView($page);

if ($error): 
?>
    <div role="alert" class="error"><?= e($error) ?></div>
<?php 
endif;

if ($page === 'home'): 
?>
    <main>
        <section class="hero">
            <div class="hero-photos">
                <img src="assets/images/men-5.jpg" alt="Signature Hoodie navy">
                <img src="assets/images/men-4.jpg" alt="Signature Hoodie black">
            </div>
            <div class="hero-copy">
                <p class="eyebrow">THE EVERYDAY COLLECTION / 2026</p>
                <h1>DOOM IS COOL<br>HOODIES</h1>
                <a class="button light" href="index.php?page=catalog&amp;category=Men">Explore hoodies <?= icon('arrow') ?></a>
            </div>
        </section>

        <section class="intro">
            <p class="eyebrow">TURN IT AROUND.</p>
            <h2>Doom is temporary.<br>Reversal is the ultimate move.</h2>
            <p>Explore our signature hoodie collection engineered for those who flip the script on despair.</p>
        </section>

        <section class="campaign">
            <img src="assets/images/men-3.jpg" alt="Signature Hoodie burgundy" loading="lazy">
            <div>
                <p class="eyebrow">BUILT FOR YOUR NEXT CHAPTER</p>
                <h2>DOOM REVERSAL<br>SIGNATURE HOODIE</h2>
                <p>When everything feels like it’s falling apart.</p>
                <a class="text-link" href="index.php?page=product&amp;id=3">Find your signature <?= icon('arrow') ?></a>
            </div>
        </section>

        <section class="split">
            <a href="index.php?page=catalog&amp;category=Men">
                <img src="assets/images/men-5.jpg" alt="Koleksi pria" loading="lazy">
                <div>
                    <h2>Feel The Fit</h2>
                    <span>SHOP MEN <?= icon('arrow') ?></span>
                </div>
            </a>
            <a href="index.php?page=catalog&amp;category=Women">
                <img src="assets/images/women-4.jpg" alt="Koleksi wanita" loading="lazy">
                <div>
                    <h2>Own Your Everyday</h2>
                    <span>SHOP WOMEN <?= icon('arrow') ?></span>
                </div>
            </a>
        </section>

        <section class="featured container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">THE ROTATION</p>
                    <h2>Everyday essentials.</h2>
                </div>
                <a class="text-link" href="index.php?page=catalog">View all <?= icon('arrow') ?></a>
            </div>
            <div class="grid">
                <?php 
                $featured = array_slice(array_values($products), 0, 3); 
                for ($i = 0; $i < count($featured); $i++) {
                    card($featured[$i]); 
                }
                ?>
            </div>
        </section>

        <section class="pants-banner">
            <img src="assets/images/pants.jpg" alt="Relic Everyday Chino Pants" loading="lazy">
            <div>
                <p class="eyebrow">BUILT FOR MOVEMENT, CUT FOR THE STREETS</p>
                <h2 class="visually-hidden">Relic Everyday.</h2>
                <a class="button" href="index.php?page=product&amp;id=13">Explore pants <?= icon('arrow') ?></a>
            </div>
        </section>
    </main>

<?php 
elseif ($page === 'catalog'):
    $category = is_string($_GET['category'] ?? null) ? $_GET['category'] : '';
    $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    $sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : '';
    $list = [];

    foreach ($products as $prod) {
        if (($category === '' || $prod['category'] === $category) && ($q === '' || stripos($prod['name'] . ' ' . $prod['color'], $q) !== false)) {
            $list[] = $prod;
        }
    }

    if ($sort === 'price-low') {
        usort($list, function($a, $b) { return $a['price'] <=> $b['price']; });
    } elseif ($sort === 'name') {
        usort($list, function($a, $b) { return strcmp($a['name'], $b['name']); });
    }

    $pages = max(1, (int)ceil(count($list) / 6)); 
    $current = max(1, min($pages, (int)($_GET['p'] ?? 1))); 
    $shown = array_slice($list, ($current - 1) * 6, 6);
?>
    <main class="container catalog">
        <div class="section-heading">
            <div>
                <p class="eyebrow">DOOM REVERSAL / COLLECTION</p>
                <h1><?= e($category ?: 'All essentials') ?></h1>
                <p class="muted"><?= count($list) ?> produk · Pilih gayamu.</p>
            </div>
            <a class="text-link" href="index.php?page=catalog">Semua koleksi <?= icon('arrow') ?></a>
        </div>

        <form class="filters" method="get">
            <input type="hidden" name="page" value="catalog">
            <input type="hidden" name="category" value="<?= e($category) ?>">
            <label class="search-field">Cari produk
                <input name="q" value="<?= e($q) ?>" placeholder="Nama produk atau warna...">
            </label>
            <label>Urutkan
                <select name="sort">
                    <option value="">Rekomendasi</option>
                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nama A–Z</option>
                    <option value="price-low" <?= $sort === 'price-low' ? 'selected' : '' ?>>Harga terendah</option>
                </select>
            </label>
            <button class="button">Terapkan</button>
        </form>

        <?php if (!$shown): ?>
            <div class="empty">
                <h2><?= $category === 'Kids' ? 'Koleksi Kids segera hadir.' : 'Produk belum ditemukan.' ?></h2>
                <p><?= $category === 'Kids' ? 'Referensi yang diberikan belum menyertakan produk Kids.' : 'Coba nama lain atau lihat semua koleksi.' ?></p>
                <a class="button" href="index.php?page=catalog">Lihat koleksi</a>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php for ($i = 0; $i < count($shown); $i++) card($shown[$i]); ?>
            </div>
        <?php endif; ?>

        <nav class="pagination" aria-label="Halaman katalog">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a <?= $current === $i ? 'aria-current="page"' : '' ?> href="index.php?<?= e(http_build_query(['page' => 'catalog', 'category' => $category, 'q' => $q, 'sort' => $sort, 'p' => $i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    </main>

<?php 
elseif ($page === 'product'): 
?>
    <main class="container product">
        <div class="breadcrumb"><a href="index.php?page=catalog&amp;category=<?= e($p['category']) ?>"><?= e($p['category']) ?></a> / <?= e($p['name']) ?></div>
        <div class="product-layout">
            <div>
                <img class="product-photo" src="assets/images/<?= e($p['image']) ?>" alt="<?= e($p['name'] . ' ' . $p['color']) ?>">
            </div>
            <div class="product-copy">
                <p class="eyebrow">DOOM REVERSAL / <?= e($p['category']) ?></p>
                <h1><?= e($p['name']) ?></h1>
                <p class="description">Born from chaos, built for resilience. An everyday essential made for your next chapter. Turn despair into your ultimate armor.</p>
                <p class="price large"><?= rupiah($p['price']) ?></p>
                <p>Color: <strong><?= e($p['color']) ?></strong></p>
                
                <form method="post">
                    <?= tokenField() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    
                    <fieldset class="sizes">
                        <legend>Choose size <span aria-hidden="true">*</span></legend>
                        <?php for ($i = 0; $i < count(SIZES); $i++): ?>
                            <label>
                                <input type="radio" name="size" value="<?= SIZES[$i] ?>" required <?= field('size') === SIZES[$i] ? 'checked' : '' ?>>
                                <span><?= SIZES[$i] ?></span>
                            </label>
                        <?php endfor; ?>
                    </fieldset>
                    
                    <label class="quantity">Quantity
                        <input type="number" name="qty" min="1" max="20" value="1" required>
                    </label>
                    
                    <button class="button full">ADD TO BAG <?= icon('cart') ?></button>
                </form>
                
                <p class="muted">Diskon 10% untuk subtotal mulai Rp 900.000.</p>
                
                <details>
                    <summary>Size &amp; Fit</summary>
                    <p>Ukuran berikut adalah panduan simulasi tugas, bukan ukuran resmi produk.</p>
                    <table>
                        <thead>
                            <tr><th>Size</th><th>Chest</th><th>Length</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $measure = [['S', '100 cm', '66 cm'], ['M', '106 cm', '68 cm'], ['L', '112 cm', '70 cm'], ['XL', '118 cm', '72 cm']]; 
                            for ($i = 0; $i < count($measure); $i++): 
                            ?>
                                <tr>
                                    <?php for ($j = 0; $j < count($measure[$i]); $j++): ?>
                                        <td><?= e($measure[$i][$j]) ?></td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </details>
                
                <details>
                    <summary>Product details</summary>
                    <p><?= $p['category'] === 'Pants' ? 'Chino pants untuk koleksi everyday.' : 'Hoodie dalam koleksi essentials.' ?> Gambar diambil dari UI referensi. Deskripsi merupakan teks presentasi untuk proyek pembelajaran.</p>
                </details>
                
                <details>
                    <summary>Reviews</summary>
                    <p>Belum ada ulasan. Angka “300” pada mockup tidak dipakai sebagai ulasan nyata.</p>
                </details>
            </div>
        </div>
    </main>

<?php 
elseif ($page === 'cart' || $page === 'checkout' || $page === 'report'):
    $rows = cartRows();
    $total = totals($rows); 
?>
    <main class="container cart">
        <p class="eyebrow">YOUR EVERYDAY ROTATION</p>
        <h1><?= $page === 'checkout' ? 'Checkout' : ($page === 'report' ? 'Laporan keranjang' : 'Your bag') ?></h1>
        
        <?php if (!$rows): ?>
            <div class="empty">
                <h2>Keranjang masih kosong.</h2>
                <p>Temukan essential untuk hari-harimu.</p>
                <a class="button" href="index.php?page=catalog">Explore the collection <?= icon('arrow') ?></a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div>
                    <?php if ($page === 'report'): ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>No</th><th>Produk</th><th>Ukuran</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr>
                                </thead>
                                <tbody>
                                    <?php for ($i = 0; $i < count($rows); $i++): $r = $rows[$i]; ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= e($r['product']['name']) ?></td>
                                            <td><?= e($r['size']) ?></td>
                                            <td><?= rupiah($r['product']['price']) ?></td>
                                            <td><?= $r['qty'] ?></td>
                                            <td><?= rupiah($r['subtotal']) ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="muted">Laporan menggunakan FOR, accumulator, diskon, dan escaping nama produk.</p>
                        <button class="button" type="button" onclick="window.print()">Cetak laporan</button>
                    <?php else: ?>
                        <?php for ($i = 0; $i < count($rows); $i++): $r = $rows[$i]; ?>
                            <article class="cart-row">
                                <a href="index.php?page=product&amp;id=<?= $r['product']['id'] ?>">
                                    <img src="assets/images/<?= e($r['product']['image']) ?>" alt="<?= e($r['product']['name']) ?>">
                                </a>
                                <div>
                                    <h3><?= e($r['product']['name']) ?></h3>
                                    <p class="muted"><?= e($r['product']['color']) ?> / Size <?= e($r['size']) ?></p>
                                    <p><?= rupiah($r['product']['price']) ?></p>
                                    
                                    <?php if ($page === 'cart'): ?>
                                        <form class="cart-controls" method="post">
                                            <?= tokenField() ?>
                                            <input type="hidden" name="key" value="<?= e($r['key']) ?>">
                                            <label>Qty
                                                <input name="qty" type="number" min="1" max="20" value="<?= $r['qty'] ?>" required>
                                            </label>
                                            <button name="action" value="update">Update</button>
                                            <button name="action" value="remove" formnovalidate>Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <p>Jumlah: <?= $r['qty'] ?></p>
                                    <?php endif; ?>
                                </div>
                                <strong><?= rupiah($r['subtotal']) ?></strong>
                            </article>
                        <?php endfor; ?>
                        
                        <?php if ($page === 'checkout'): ?>
                            <section class="checkout-form">
                                <h2>Alamat pengiriman</h2>
                                <p class="muted">Simulasi lokal. Tidak ada pembayaran atau pengiriman nyata.</p>
                                <form method="post">
                                    <?= tokenField() ?>
                                    <input type="hidden" name="action" value="checkout">
                                    <label>Nama penerima
                                        <input name="name" maxlength="100" value="<?= e(field('name') ?: $_SESSION['user']['name']) ?>" required>
                                    </label>
                                    <label>Nomor telepon
                                        <input name="phone" type="tel" minlength="8" maxlength="20" value="<?= e(field('phone')) ?>" required>
                                    </label>
                                    <label>Alamat lengkap
                                        <textarea name="address" minlength="10" maxlength="1000" rows="4" required><?= e(field('address')) ?></textarea>
                                    </label>
                                    <button class="button full">Konfirmasi pesanan simulasi <?= icon('arrow') ?></button>
                                </form>
                            </section>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <aside class="summary">
                    <p class="eyebrow">ORDER SUMMARY</p>
                    <h2>Your next chapter.</h2>
                    <dl>
                        <div>
                            <dt>Subtotal</dt>
                            <dd><?= rupiah($total['subtotal']) ?></dd>
                        </div>
                        <div>
                            <dt>Diskon <?= $total['discount'] ? '10%' : '' ?></dt>
                            <dd>− <?= rupiah($total['discount']) ?></dd>
                        </div>
                        <div>
                            <dt>Pengiriman (simulasi)</dt>
                            <dd>Gratis</dd>
                        </div>
                        <div class="grand">
                            <dt>Total</dt>
                            <dd><?= rupiah($total['total']) ?></dd>
                        </div>
                    </dl>
                    
                    <?php if ($page === 'cart'): ?>
                        <a class="button full" href="index.php?page=checkout">CHECKOUT <?= icon('arrow') ?></a>
                        <a class="text-link" href="index.php?page=report">Lihat laporan keranjang</a>
                    <?php endif; ?>
                    
                    <p class="muted">Diskon otomatis 10% untuk subtotal minimal Rp 900.000.</p>
                    <a href="index.php?page=catalog" class="text-link">Continue shopping</a>
                </aside>
            </div>
        <?php endif; ?>
    </main>

<?php 
elseif ($page === 'login' || $page === 'register'): 
    $register = $page === 'register'; 
?>
    <main class="auth">
        <a href="index.php"><img class="auth-logo" src="assets/images/logo.png" alt="DOOM REVERSAL"></a>
        <h1><?= $register ? 'Register' : 'Login' ?></h1>
        <p class="muted"><?= $register ? 'Start your next chapter.' : 'Welcome back. Turn it around.' ?></p>
        
        <form method="post">
            <?= tokenField() ?>
            <input type="hidden" name="action" value="<?= $page ?>">
            
            <?php if ($register): ?>
                <div class="two-fields">
                    <label>First name
                        <input name="first" maxlength="80" placeholder="first name*" value="<?= e(field('first')) ?>" autocomplete="given-name" required>
                    </label>
                    <label>Last name
                        <input name="last" maxlength="80" value="<?= e(field('last')) ?>" placeholder="last name" autocomplete="family-name">
                    </label>
                </div>
            <?php endif; ?>
            
            <label>Email
                <input name="email" type="email" maxlength="190" value="<?= e(field('email')) ?>" placeholder="email*" autocomplete="email" required>
            </label>
            
            <label>Password
                <div class="password-field">
                    <input id="password" name="password" type="password" <?= $register ? 'minlength="8" maxlength="72"' : '' ?> placeholder="password*" autocomplete="<?= $register ? 'new-password' : 'current-password' ?>" required>
                    <button type="button" data-password="password" aria-label="Tampilkan password">Show</button>
                </div>
            </label>
            
            <?php if ($register): ?>
                <label>Confirm password
                    <input name="confirm" type="password" minlength="8" maxlength="72" placeholder="confirm password*" autocomplete="new-password" required>
                </label>
                <p class="muted">Password minimal 8 karakter. Akun tersimpan di komputer lokal.</p>
            <?php else: ?>
                <div class="auth-options">
                    <span class="muted">Login berlaku selama sesi browser.</span>
                    <a href="index.php?page=help">Forgot password?</a>
                </div>
            <?php endif; ?>
            
            <button class="button full auth-submit">Submit <?= icon('arrow') ?></button>
        </form>
        
        <p><?= $register ? 'Sudah punya akun?' : 'Belum punya akun?' ?> <a class="text-link" href="index.php?page=<?= $register ? 'login' : 'register' ?>"><?= $register ? 'Login' : 'Register' ?></a></p>
    </main>

<?php 
elseif ($page === 'account'):
    $statement = db()->prepare('SELECT id, code, created_at AS created, recipient_name AS name, phone, address, subtotal, discount, total, status FROM orders WHERE user_id = ? ORDER BY id DESC');
    $statement->execute([$_SESSION['user']['id']]);
    $orders = $statement->fetchAll();
    $itemStatement = db()->prepare('SELECT product_id, product_name, color, image, size, quantity, unit_price, subtotal FROM order_items WHERE order_id = ? ORDER BY id');
    
    foreach ($orders as &$order) {
        $paymentQuery = db()->prepare('SELECT status, method FROM payments WHERE order_id=?');
        $paymentQuery->execute([$order['id']]);
        $order['payment'] = $paymentQuery->fetch();
        $order['totals'] = ['subtotal' => (int)$order['subtotal'], 'discount' => (int)$order['discount'], 'total' => (int)$order['total']];
        $itemStatement->execute([$order['id']]);
        $order['rows'] = [];
        foreach ($itemStatement->fetchAll() as $item) {
            $order['rows'][] = [
                'product' => [
                    'id' => $item['product_id'], 
                    'name' => $item['product_name'], 
                    'color' => $item['color'], 
                    'image' => $item['image'], 
                    'price' => (int)$item['unit_price']
                ], 
                'size' => $item['size'], 
                'qty' => (int)$item['quantity'], 
                'subtotal' => (int)$item['subtotal']
            ];
        }
    }
    unset($order); 
?>
    <main class="container account">
        <p class="eyebrow">YOUR ACCOUNT</p>
        <div class="section-heading">
            <div>
                <h1>Hello, <?= e($_SESSION['user']['name']) ?>.</h1>
                <p class="muted"><?= e($_SESSION['user']['email']) ?></p>
            </div>
            <form method="post">
                <?= tokenField() ?>
                <button class="button outline" name="action" value="logout">Logout</button>
            </form>
        </div>
        
        <h2>Riwayat pesanan</h2>
        
        <?php if (!$orders): ?>
            <div class="empty">
                <p>Belum ada pesanan. Mulai koleksi pertamamu.</p>
                <a class="button" href="index.php?page=catalog">Shop now</a>
            </div>
        <?php endif; ?>
        
        <?php foreach ($orders as $o): ?>
            <details class="order">
                <summary><?= e($o['code']) ?> <span><?= rupiah($o['totals']['total']) ?></span></summary>
                <p><?= e($o['created'] . ' · ' . $o['status']) ?></p>
                <p>Status pembayaran: <strong><?= e(paymentLabel($o['payment']['status'] ?? 'pending')) ?></strong></p>
                <a class="button outline" href="index.php?page=payment&amp;order=<?= (int)$o['id'] ?>"><?= ($o['payment']['status'] ?? 'pending') === 'pending' ? 'Lanjut bayar simulasi' : 'Lihat bukti simulasi' ?></a>
                <p><?= e($o['name']) ?><br><?= nl2br(e($o['address'])) ?><br><?= e($o['phone']) ?></p>
                
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Produk</th><th>Ukuran</th><th>Jumlah</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($o['rows'] as $r): ?>
                                <tr>
                                    <td><?= e($r['product']['name']) ?></td>
                                    <td><?= e($r['size']) ?></td>
                                    <td><?= $r['qty'] ?></td>
                                    <td><?= rupiah($r['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <p>Subtotal: <?= rupiah($o['totals']['subtotal']) ?> · Diskon: <?= rupiah($o['totals']['discount']) ?></p>
            </details>
        <?php endforeach; ?>
    </main>

<?php 
elseif ($page === 'payment'): 
    $o = $paymentOrder; 
?>
    <main class="container payment">
        <p class="eyebrow">PAYMENT DEMO / DOOM REVERSAL</p>
        <h1><?= $o['payment_status'] === 'pending' ? 'Pembayaran simulasi' : 'Bukti transaksi simulasi' ?></h1>
        
        <div class="notice payment-notice">Mode tugas: tidak ada transfer uang nyata, rekening asli, QR pembayaran atau koneksi gateway.</div>
        
        <div class="cart-layout">
            <section>
                <h2><?= e($o['code']) ?></h2>
                <p>Nama penerima: <?= e($o['recipient_name']) ?></p>
                <p class="muted">Status: <strong><?= e(paymentLabel($o['payment_status'])) ?></strong></p>
                
                <?php if ($o['payment_status'] === 'pending'): ?>
                    <form class="payment-methods" method="post">
                        <?= tokenField() ?>
                        <input type="hidden" name="action" value="pay">
                        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                        <fieldset>
                            <legend>Pilih metode demo</legend>
                            <label>
                                <input type="radio" name="method" value="bank" required>
                                <span><strong>Transfer bank — simulasi</strong><small>Konfirmasi menandai pembayaran demo selesai. Tidak ada nomor rekening.</small></span>
                            </label>
                            <label>
                                <input type="radio" name="method" value="ewallet" required>
                                <span><strong>E-wallet — simulasi</strong><small>Tidak meminta PIN, OTP, atau login dompet digital.</small></span>
                            </label>
                            <label>
                                <input type="radio" name="method" value="cod" required>
                                <span><strong>COD — simulasi</strong><small>Bayar saat barang diterima. Status tetap belum lunas.</small></span>
                            </label>
                        </fieldset>
                        <button class="button full">Konfirmasi metode simulasi <?= icon('arrow') ?></button>
                    </form>
                <?php else: ?>
                    <dl class="receipt">
                        <div>
                            <dt>Metode</dt>
                            <dd><?= e(methodLabel($o['method'])) ?></dd>
                        </div>
                        <div>
                            <dt>Referensi demo</dt>
                            <dd><?= e($o['reference']) ?></dd>
                        </div>
                        <div>
                            <dt>Waktu konfirmasi bayar</dt>
                            <dd><?= e($o['paid_at'] ?? 'Belum dibayar (COD)') ?></dd>
                        </div>
                    </dl>
                    <p><?= $o['payment_status'] === 'cod_pending' ? 'COD belum berarti lunas. Demo ini belum memiliki proses penerimaan barang.' : 'Pembayaran ini hanya simulasi untuk presentasi tugas.' ?></p>
                    <button class="button" type="button" onclick="window.print()">Cetak bukti simulasi</button>
                <?php endif; ?>
                
                <p><a class="text-link" href="index.php?page=account">Kembali ke riwayat pesanan</a></p>
            </section>
            
            <aside class="summary">
                <p class="eyebrow">ORDER SUMMARY</p>
                <dl>
                    <div>
                        <dt>Subtotal</dt>
                        <dd><?= rupiah((int)$o['subtotal']) ?></dd>
                    </div>
                    <div>
                        <dt>Diskon</dt>
                        <dd>− <?= rupiah((int)$o['discount']) ?></dd>
                    </div>
                    <div class="grand">
                        <dt>Total simulasi</dt>
                        <dd><?= rupiah((int)$o['total']) ?></dd>
                    </div>
                </dl>
                <p class="muted">Nominal berasal dari pesanan di database. Status pembayaran dan referensi disimpan pada tabel payments.</p>
            </aside>
        </div>
    </main>

<?php 
elseif ($page === 'help'): 
?>
    <main class="container help">
        <p class="eyebrow">ACCOUNT HELP</p>
        <h1>Lupa password?</h1>
        <p>Versi pembelajaran lokal ini belum terhubung layanan email, sehingga belum memiliki reset password otomatis.</p>
        <p>Gunakan akun lain atau buat akun baru dengan email berbeda untuk melanjutkan demo. Jangan gunakan password akun asli untuk tugas ini.</p>
        <a class="button" href="index.php?page=register">Buat akun demo</a>
    </main>

<?php 
else: 
?>
    <main class="container empty">
        <h1>Halaman tidak ditemukan.</h1>
        <a class="button" href="index.php">Kembali ke beranda</a>
    </main>

<?php 
endif; 

footerView(); 
?>