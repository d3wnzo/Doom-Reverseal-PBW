<?php
// Pertemuan 3: hasil query diubah menjadi array asosiatif berdasarkan ID.
$products = [];
$statement = db()->query('SELECT id, name, category, color, price, image, available FROM products ORDER BY id');

foreach ($statement->fetchAll() as $product) {
    $product['id'] = (int)$product['id'];
    $product['price'] = (int)$product['price'];
    $product['available'] = (bool)$product['available'];
    $products[$product['id']] = $product;
}

const SIZES = ['S', 'M', 'L', 'XL'];