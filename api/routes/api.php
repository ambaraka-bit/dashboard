<?php
// ============================================================
//  routes/api.php
//  All API endpoint definitions
// ============================================================

use App\Router;
use App\Repository\OrderRepository;
use App\Response\JsonResponse;

/** @var Router $router  (injected from index.php) */

$repo = new OrderRepository();

// ── GET /api/health ────────────────────────────────────────
// Quick connectivity check for monitoring / the frontend
$router->get('/health', function () use ($repo): void {
    try {
        // Ping the DB
        \App\Database\Connection::getInstance()->query('SELECT 1');
        JsonResponse::success(['status' => 'ok', 'db' => 'connected']);
    } catch (\Throwable $e) {
        JsonResponse::error('Database unreachable', 503);
    }
});

// ── GET /api/dashboard ────────────────────────────────────
// Single call that returns ALL chart data — used by the frontend
$router->get('/dashboard', function () use ($repo): void {
    JsonResponse::success([
        'scorecards'     => $repo->getScoreCards(),
        'by_category'    => $repo->getByCategory(),
        'by_status'      => $repo->getByCourierStatus(),
        'by_order_status'=> $repo->getByOrderStatus(),
        'by_size'        => $repo->getBySize(),
        'by_fulfilment'  => $repo->getByFulfilment(),
        'by_channel'     => $repo->getBySalesChannel(),
        'by_b2b'         => $repo->getByB2B(),
        'top_states'     => $repo->getTopStates(),
        'top_cities'     => $repo->getTopCities(),
        'revenue_trend'  => $repo->getRevenueTrend(12),
        'daily_orders'   => $repo->getDailyOrderCount(30),
    ]);
});

// ── GET /api/scorecards ───────────────────────────────────
$router->get('/scorecards', function () use ($repo): void {
    JsonResponse::success($repo->getScoreCards());
});

// ── GET /api/charts/category ──────────────────────────────
$router->get('/charts/category', function () use ($repo): void {
    $limit = (int) ($_GET['limit'] ?? 8);
    JsonResponse::success($repo->getByCategory($limit));
});

// ── GET /api/charts/status ────────────────────────────────
$router->get('/charts/status', function () use ($repo): void {
    JsonResponse::success($repo->getByCourierStatus());
});

// ── GET /api/charts/size ──────────────────────────────────
$router->get('/charts/size', function () use ($repo): void {
    $limit = (int) ($_GET['limit'] ?? 10);
    JsonResponse::success($repo->getBySize($limit));
});

// ── GET /api/charts/fulfilment ────────────────────────────
$router->get('/charts/fulfilment', function () use ($repo): void {
    JsonResponse::success($repo->getByFulfilment());
});

// ── GET /api/charts/channel ───────────────────────────────
$router->get('/charts/channel', function () use ($repo): void {
    JsonResponse::success($repo->getBySalesChannel());
});

// ── GET /api/charts/b2b ───────────────────────────────────
$router->get('/charts/b2b', function () use ($repo): void {
    JsonResponse::success($repo->getByB2B());
});

// ── GET /api/geo/states?limit=10 ──────────────────────────
$router->get('/geo/states', function () use ($repo): void {
    $limit = (int) ($_GET['limit'] ?? 10);
    JsonResponse::success($repo->getTopStates($limit));
});

// ── GET /api/geo/cities?limit=10 ──────────────────────────
$router->get('/geo/cities', function () use ($repo): void {
    $limit = (int) ($_GET['limit'] ?? 10);
    JsonResponse::success($repo->getTopCities($limit));
});

// ── GET /api/timeseries/revenue?months=12 ────────────────
$router->get('/timeseries/revenue', function () use ($repo): void {
    $months = min((int) ($_GET['months'] ?? 12), 60);
    JsonResponse::success($repo->getRevenueTrend($months));
});

// ── GET /api/timeseries/daily?days=30 ────────────────────
$router->get('/timeseries/daily', function () use ($repo): void {
    $days = min((int) ($_GET['days'] ?? 30), 365);
    JsonResponse::success($repo->getDailyOrderCount($days));
});

// ── GET /api/orders?page=1&limit=10&status=&category=&size=&state= ──
$router->get('/orders', function () use ($repo): void {
    $cfg   = require __DIR__ . '/../config/app.php';
    $page  = max(1, (int) ($_GET['page']  ?? 1));
    $limit = min(
        max(1, (int) ($_GET['limit'] ?? $cfg['default_limit'])),
        $cfg['max_limit']
    );

    $filters = [];
    foreach (['status', 'category', 'size', 'state'] as $key) {
        if (!empty($_GET[$key])) {
            $filters[$key] = trim((string) $_GET[$key]);
        }
    }

    JsonResponse::success($repo->getOrders($page, $limit, $filters));
});
