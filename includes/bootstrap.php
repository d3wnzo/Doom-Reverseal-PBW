<?php
// Pertemuan 1–2: PHP server-side, include, fungsi prosedural, pemisahan tanggung jawab.
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
]);
session_name('DOOMREVERSALSESSID');
session_start();
date_default_timezone_set('Asia/Jakarta');

require __DIR__ . '/database.php';

try { 
    require __DIR__ . '/products.php'; 
} catch (Throwable $ex) {
    http_response_code(503);
    require __DIR__ . '/setup-help.php';
    exit;
}

const DISCOUNT_RATE = 0.10; // float; pertemuan 3–4
const DISCOUNT_THRESHOLD = 900000;

function e($text): string { 
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); 
}

function rupiah(int $amount): string { 
    return 'Rp ' . number_format($amount, 0, ',', '.'); 
}

function go(string $url): void { 
    header('Location: ' . $url); 
    exit; 
}

function flash(string $text): void { 
    $_SESSION['flash'] = $text; 
}

function csrf(): string { 
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); 
    }
    return $_SESSION['csrf']; 
}

function tokenField(): string { 
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; 
}

function validCsrf(): bool { 
    return is_string($_POST['csrf'] ?? null) && hash_equals(csrf(), $_POST['csrf']); 
}

function field(string $name): string { 
    return is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : ''; 
}

function qtyValue($value): int { 
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]) ?: 0; 
}

function cartRows(): array {
    global $products; 
    $rows = []; 
    $cart = $_SESSION['cart'] ?? [];
    
    // Pertemuan 5: WHILE membaca setiap varian pada keranjang.
    $keys = array_keys($cart); 
    $i = 0;
    
    while ($i < count($keys)) {
        $key = $keys[$i]; 
        $entry = $cart[$key];
        
        if (isset($products[$entry['id']])) {
            $rows[] = [
                'key' => $key,
                'product' => $products[$entry['id']],
                'size' => $entry['size'],
                'qty' => $entry['qty'],
                'subtotal' => $products[$entry['id']]['price'] * $entry['qty']
            ];
        }
        $i++;
    }
    return $rows;
}

function totals(array $rows): array {
    $subtotal = 0; // Pertemuan 7: FOR + accumulator; pertemuan 4: operator aritmatika.
    
    for ($i = 0; $i < count($rows); $i++) {
        $subtotal += $rows[$i]['subtotal'];
    }
    
    $discount = $subtotal >= DISCOUNT_THRESHOLD ? (int)round($subtotal * DISCOUNT_RATE) : 0;
    
    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => $subtotal - $discount
    ];
}

function icon(string $name): string {
    $paths = [
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'user' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="9" r="3"/><path d="M6 19c1-6 11-6 12 0"/>',
        'cart' => '<path d="M2 3h3l2 13h13l2-9H6"/><circle cx="9" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>'
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

function paymentLabel(string $status): string {
    return [
        'pending' => 'Menunggu pembayaran simulasi',
        'paid_demo' => 'Lunas (simulasi)',
        'cod_pending' => 'COD - belum dibayar'
    ][$status] ?? 'Status tidak dikenal';
}

function methodLabel(?string $method): string {
    return [
        'bank' => 'Transfer bank (demo)',
        'ewallet' => 'E-wallet (demo)',
        'cod' => 'COD (demo)'
    ][$method ?? ''] ?? 'Belum dipilih';
}