<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrf()) { 
        http_response_code(403); 
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.'; 
    } else {
        $action = field('action');
        try {
            if ($action === 'add') {
                $id = (int)field('id'); 
                $size = field('size'); 
                $qty = qtyValue(field('qty'));
                
                if (!isset($products[$id]) || !$products[$id]['available'] || !in_array($size, SIZES, true) || !$qty) {
                    $error = 'Pilih ukuran dan jumlah 1–20.';
                } else {
                    $key = $id . '-' . $size; 
                    $old = $_SESSION['cart'][$key]['qty'] ?? 0;
                    
                    if ($old + $qty > 20) {
                        $error = 'Maksimal 20 produk per ukuran.';
                    } else { 
                        $_SESSION['cart'][$key] = ['id' => $id, 'size' => $size, 'qty' => $old + $qty]; 
                        flash('Produk ditambahkan ke keranjang.'); 
                        go('index.php?page=cart'); 
                    }
                }
            } elseif ($action === 'update') {
                $key = field('key'); 
                $qty = qtyValue(field('qty'));
                
                if (!isset($_SESSION['cart'][$key]) || !$qty) {
                    $error = 'Jumlah harus 1–20.';
                } else { 
                    $_SESSION['cart'][$key]['qty'] = $qty; 
                    flash('Keranjang diperbarui.'); 
                    go('index.php?page=cart'); 
                }
            } elseif ($action === 'remove') {
                unset($_SESSION['cart'][field('key')]); 
                flash('Produk dihapus.'); 
                go('index.php?page=cart');
            } elseif ($action === 'register') {
                $first = field('first'); 
                $last = field('last'); 
                $email = strtolower(field('email')); 
                $password = field('password');
                
                if ($first === '' || strlen($first) > 80 || strlen($last) > 80 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || strlen($password) < 8 || strlen($password) > 72) {
                    $error = 'Isi nama depan, email valid, dan password 8–72 karakter.';
                } elseif ($password !== field('confirm')) {
                    $error = 'Konfirmasi password tidak sama.';
                } else {
                    $statement = db()->prepare('SELECT id FROM users WHERE email = ?');
                    $statement->execute([$email]);
                    
                    if ($statement->fetch()) {
                        throw new RuntimeException('Email sudah terdaftar. Silakan login.');
                    }
                    
                    $statement = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
                    try { 
                        $statement->execute([trim($first . ' ' . $last), $email, password_hash($password, PASSWORD_DEFAULT)]); 
                    } catch (PDOException $ex) {
                        if (($ex->errorInfo[1] ?? 0) === 1062) {
                            throw new RuntimeException('Email sudah terdaftar. Silakan login.');
                        }
                        throw $ex;
                    }
                    
                    $user = ['id' => (int)db()->lastInsertId(), 'name' => trim($first . ' ' . $last), 'email' => $email];
                    session_regenerate_id(true); 
                    unset($user['password_hash']); 
                    $_SESSION['user'] = $user;
                    
                    flash('Akun berhasil dibuat. Selamat datang, ' . $user['name'] . '.'); 
                    go('index.php?page=account');
                }
            } elseif ($action === 'login') {
                $email = strtolower(field('email')); 
                $password = field('password');
                
                if (($_SESSION['login_block_until'] ?? 0) > time()) {
                    $error = 'Terlalu banyak percobaan. Coba lagi dalam satu menit.';
                } else {
                    $statement = db()->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
                    $statement->execute([$email]);
                    $user = $statement->fetch();
                    
                    if (!$user || !password_verify($password, $user['password_hash'])) {
                        $error = 'Email atau password salah.'; 
                        $_SESSION['login_failures'] = ($_SESSION['login_failures'] ?? 0) + 1;
                        
                        if ($_SESSION['login_failures'] >= 5) { 
                            $_SESSION['login_block_until'] = time() + 60; 
                            $_SESSION['login_failures'] = 0; 
                        }
                    } else {
                        session_regenerate_id(true); 
                        unset($user['password_hash']); 
                        $_SESSION['user'] = $user; 
                        $_SESSION['login_failures'] = 0;
                        
                        flash('Berhasil login.'); 
                        go('index.php?page=account');
                    }
                }
            } elseif ($action === 'logout') {
                unset($_SESSION['user']); 
                session_regenerate_id(true); 
                flash('Kamu sudah logout.'); 
                go('index.php');
            } elseif ($action === 'checkout') {
                $rows = cartRows(); 
                $name = field('name'); 
                $address = field('address'); 
                $phone = field('phone');
                
                if (empty($_SESSION['user'])) { 
                    flash('Login dulu untuk checkout.'); 
                    go('index.php?page=login'); 
                }
                if (!$rows) {
                    $error = 'Keranjang masih kosong.';
                } elseif (strlen($name) < 2 || strlen($name) > 100 || strlen($address) < 10 || strlen($address) > 1000 || !preg_match('/^[0-9+ ()-]{8,20}$/', $phone)) {
                    $error = 'Isi nama, nomor telepon valid, dan alamat lengkap minimal 10 karakter.';
                } else {
                    $connection = db();
                    $connection->beginTransaction();
                    try {
                        // Pertemuan 6: kandidat kode dibuat minimal satu kali.
                        $check = $connection->prepare('SELECT id FROM orders WHERE code = ?');
                        do {
                            $code = 'DR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                            $check->execute([$code]);
                            $exists = (bool)$check->fetch();
                        } while ($exists);
                        
                        $total = totals($rows);
                        $statement = $connection->prepare('INSERT INTO orders (code, user_id, recipient_name, phone, address, subtotal, discount, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $statement->execute([$code, $_SESSION['user']['id'], $name, $phone, $address, $total['subtotal'], $total['discount'], $total['total'], 'Simulasi - tidak ada pembayaran']);
                        
                        $orderId = (int)$connection->lastInsertId();
                        $itemStatement = $connection->prepare('INSERT INTO order_items (order_id, product_id, product_name, color, image, size, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        
                        for ($i = 0; $i < count($rows); $i++) {
                            $r = $rows[$i]; 
                            $p = $r['product'];
                            $itemStatement->execute([$orderId, $p['id'], $p['name'], $p['color'], $p['image'], $r['size'], $r['qty'], $p['price'], $r['subtotal']]);
                        }
                        
                        $paymentStatement = $connection->prepare('INSERT INTO payments (order_id, amount, status) VALUES (?, ?, ?)');
                        $paymentStatement->execute([$orderId, $total['total'], 'pending']);
                        
                        $connection->commit();
                    } catch (Throwable $ex) {
                        if ($connection->inTransaction()) {
                            $connection->rollBack();
                        }
                        throw $ex;
                    }
                    
                    $_SESSION['cart'] = []; 
                    flash('Pesanan ' . $code . ' tersimpan. Lanjut pilih pembayaran simulasi.'); 
                    go('index.php?page=payment&order=' . $orderId);
                }
            } elseif ($action === 'pay') {
                if (empty($_SESSION['user'])) { 
                    flash('Login dulu untuk pembayaran.'); 
                    go('index.php?page=login'); 
                }
                
                $orderId = (int)field('order_id'); 
                $method = field('method');
                
                if (!in_array($method, ['bank', 'ewallet', 'cod'], true)) {
                    $error = 'Pilih metode pembayaran simulasi.';
                } else {
                    $connection = db(); 
                    $connection->beginTransaction();
                    try {
                        $statement = $connection->prepare('SELECT o.id, o.total, p.status FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.id=? AND o.user_id=? FOR UPDATE');
                        $statement->execute([$orderId, $_SESSION['user']['id']]);
                        $payment = $statement->fetch();
                        
                        if (!$payment) {
                            throw new RuntimeException('Pesanan tidak ditemukan.');
                        }
                        if ($payment['status'] !== 'pending') {
                            throw new RuntimeException('Pembayaran pesanan ini sudah dikonfirmasi.');
                        }
                        
                        $reference = 'DEMO-' . strtoupper(bin2hex(random_bytes(8)));
                        $status = $method === 'cod' ? 'cod_pending' : 'paid_demo';
                        
                        $statement = $connection->prepare('UPDATE payments SET method=?, amount=?, status=?, reference=?, paid_at=? WHERE order_id=? AND status=?');
                        $statement->execute([$method, (int)$payment['total'], $status, $reference, $method === 'cod' ? null : date('Y-m-d H:i:s'), $orderId, 'pending']);
                        
                        $statement = $connection->prepare('UPDATE orders SET status=? WHERE id=?');
                        $statement->execute([$method === 'cod' ? 'Simulasi COD - bayar saat diterima' : 'Pembayaran simulasi selesai', $orderId]);
                        
                        $connection->commit();
                    } catch (Throwable $ex) {
                        if ($connection->inTransaction()) {
                            $connection->rollBack();
                        }
                        throw $ex;
                    }
                    
                    flash($method === 'cod' ? 'COD simulasi dikonfirmasi. Status belum lunas.' : 'Pembayaran simulasi berhasil. Tidak ada uang nyata yang diproses.');
                    go('index.php?page=payment&order=' . $orderId);
                }
            }
        } catch (Throwable $ex) { 
            $error = ($ex instanceof RuntimeException && !($ex instanceof PDOException)) ? $ex->getMessage() : 'Data belum bisa diproses. Periksa koneksi dan struktur database, lalu coba lagi.'; 
        }
    }
}