<?php

declare(strict_types = 1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use Slim\App;

return function (App $app) {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/login', [AuthController::class, 'loginView']);
    $app->get('/signup', [AuthController::class, 'signupView']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/signup', [AuthController::class, 'signup']);
};