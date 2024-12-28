<?php

declare(strict_types = 1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/', [HomeController::class, 'index'])->add(AuthMiddleware::class);

    $app->get('/login', [AuthController::class, 'loginView'])->add(GuestMiddleware::class);
    $app->get('/signup', [AuthController::class, 'signupView'])->add(GuestMiddleware::class);
    
    $app->post('/login', [AuthController::class, 'login'])->add(GuestMiddleware::class);
    $app->post('/signup', [AuthController::class, 'signup'])->add(GuestMiddleware::class);
    $app->post('/logout', [AuthController::class, 'logout'])->add(AuthMiddleware::class);
};