<?php
require_once __DIR__ . '/user_store_helpers.php';

$id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
$product = null;
$errorMessage = '';

if ($id === '' || ctype_digit($id) === false) {
    $errorMessage = 'Invalid product ID.';
} else {
    $apiData = fetch_json_from_url('https://dummyjson.com/products/' . urlencode($id));
    if ($apiData === null) {
        $errorMessage = 'Product not found or external API is unavailable.';
    } else {
        $product = $apiData;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <a href="products.php" class="d-inline-block mb-3">&larr; Back to Products</a>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger"><?php echo h($errorMessage); ?></div>
        <?php elseif ($product !== null): ?>
            <?php
                $title = (string) ($product['title'] ?? 'Untitled Product');
                $description = (string) ($product['description'] ?? '');
                $price = (float) ($product['price'] ?? 0);
                $discount = (float) ($product['discountPercentage'] ?? 0);
                $rating = (float) ($product['rating'] ?? 0);
                $stock = (int) ($product['stock'] ?? 0);
                $brand = (string) ($product['brand'] ?? '');
                $category = (string) ($product['category'] ?? '');
                $thumbnail = (string) ($product['thumbnail'] ?? '');
            ?>
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <?php if ($thumbnail !== ''): ?>
                        <img src="<?php echo h($thumbnail); ?>" alt="<?php echo h($title); ?>" style="height: 90px; width: 90px; object-fit: contain;" class="mb-3">
                    <?php endif; ?>

                    <h1 class="h2 mb-3"><?php echo h($title); ?></h1>
                    <p class="mb-3"><?php echo h($description); ?></p>
                    <p>Price: $<?php echo h(number_format($price, 2)); ?></p>
                    <p>Discount: <?php echo h(number_format($discount, 2)); ?>%</p>
                    <p>Rating: <?php echo h(number_format($rating, 2)); ?>/5</p>
                    <p>Stock: <?php echo h((string) $stock); ?></p>
                    <p>Brand: <?php echo h($brand); ?></p>
                    <p class="mb-0">Category: <?php echo h($category); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
