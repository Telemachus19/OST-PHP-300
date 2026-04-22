<?php
require_once __DIR__ . '/user_store_helpers.php';

$apiData = fetch_json_from_url('https://dummyjson.com/products');
$products = is_array($apiData['products'] ?? null) ? $apiData['products'] : [];
$apiError = $apiData === null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <h1 class="h2 mb-4">Product Catalog Dashboard</h1>

        <?php if ($apiError): ?>
            <div class="alert alert-danger">Unable to load products from external API right now.</div>
        <?php elseif ($products === []): ?>
            <div class="alert alert-info">No products available.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <?php
                        $id = (int) ($product['id'] ?? 0);
                        $title = (string) ($product['title'] ?? 'Untitled Product');
                        $description = (string) ($product['description'] ?? '');
                        $price = (float) ($product['price'] ?? 0);
                        $rating = (float) ($product['rating'] ?? 0);
                        $thumbnail = (string) ($product['thumbnail'] ?? '');
                        $detailsUrl = 'product.php?id=' . urlencode((string) $id);
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <a href="<?php echo h($detailsUrl); ?>" class="text-decoration-none text-dark">
                                <?php if ($thumbnail !== ''): ?>
                                    <img src="<?php echo h($thumbnail); ?>" class="card-img-top" alt="<?php echo h($title); ?>" style="height: 280px; object-fit: contain; background: #f8f9fa;">
                                <?php endif; ?>
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h2 class="h4 mb-3">
                                    <a href="<?php echo h($detailsUrl); ?>"><?php echo h($title); ?></a>
                                </h2>
                                <p class="mb-3"><?php echo h($description); ?></p>
                                <p class="mb-2">$<?php echo h(number_format($price, 2)); ?></p>
                                <p class="mb-0">Rating: <?php echo h(number_format($rating, 2)); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
