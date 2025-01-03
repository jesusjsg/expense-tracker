<?php

declare(strict_types = 1);

use App\Controllers\AuthController;
use App\Controllers\CategoriesController;
use App\Controllers\HomeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/', [HomeController::class, 'index'])->add(AuthMiddleware::class);

    $app->group('', function (RouteCollectorProxy $guest) {
        $guest->get('/login', [AuthController::class, 'loginView']);
        $guest->get('/signup', [AuthController::class, 'signupView']);
        $guest->post('/login', [AuthController::class, 'login']);
        $guest->post('/signup', [AuthController::class, 'signup']);
    })->add(GuestMiddleware::class);

    $app->post('/logout', [AuthController::class, 'logout'])->add(AuthMiddleware::class);

    $app->group('/categories', function (RouteCollectorProxy $categories) {
        $categories->get('', [CategoriesController::class, 'index']);
        $categories->post('', [CategoriesController::class, 'store']);
        $categories->delete('/{id}', [CategoriesController::class, 'delete']);
        $categories->get('/{id}', [CategoriesController::class, 'get']);
    })->add(AuthMiddleware::class);
};