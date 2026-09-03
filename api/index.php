<?php
// Vercel Serverless Function Entry Point for EcoMart

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static assets directly if requested through API route
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico|svg)$/', $uri)) {
    return false;
}

// Route to corresponding PHP file
switch ($uri) {
    case '/cart.php':
    case '/cart':
        require __DIR__ . '/../cart.php';
        break;
    case '/checkout.php':
    case '/checkout':
        require __DIR__ . '/../checkout.php';
        break;
    case '/dashboard.php':
    case '/dashboard':
        require __DIR__ . '/../dashboard.php';
        break;
    case '/login.php':
    case '/login':
        require __DIR__ . '/../login.php';
        break;
    case '/logout.php':
    case '/logout':
        require __DIR__ . '/../logout.php';
        break;
    case '/watch.php':
    case '/watch':
        require __DIR__ . '/../watch.php';
        break;
    case '/admin':
    case '/admin/':
    case '/admin/index.php':
        require __DIR__ . '/../admin/index.php';
        break;
    case '/admin/products.php':
        require __DIR__ . '/../admin/products.php';
        break;
    case '/admin/users.php':
        require __DIR__ . '/../admin/users.php';
        break;
    case '/admin/categories.php':
        require __DIR__ . '/../admin/categories.php';
        break;
    default:
        require __DIR__ . '/../index.php';
        break;
}
