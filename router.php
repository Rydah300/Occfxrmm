<?php
// router.php — Clean URL handler (no .php extension)
session_start();

// Get request URI
$request = $_SERVER['REQUEST_URI'];
$request = strtok($request, '?');

// Route map — clean URLs to PHP files
$routes = [
    '/' => 'index.php',
    '' => 'index.php',
    '/login' => 'login.php',
    '/dashboard' => 'index.php',
    '/admin' => 'admin.php',
    '/backend' => 'backend.php',
    '/logout' => 'logout_handler.php',
];

// Handle logout
if ($request === '/logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

// Route to PHP file
if (isset($routes[$request])) {
    require_once $routes[$request];
    exit;
}

// Serve static files (CSS, JS, images, uploads)
$file_path = __DIR__ . $request;
if (file_exists($file_path) && !is_dir($file_path)) {
    $ext = pathinfo($file_path, PATHINFO_EXTENSION);
    $mime_types = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    
    if (isset($mime_types[$ext])) {
        header('Content-Type: ' . $mime_types[$ext]);
    }
    readfile($file_path);
    exit;
}

// 404
http_response_code(404);
echo "<h1 style='color:#a855f7;text-align:center;margin-top:20vh;font-family:sans-serif;'>404 — Page not found</h1>";
?>
