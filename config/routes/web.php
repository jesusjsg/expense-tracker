<?php

declare(strict_types = 1);

use App\Controllers\AuthController;
use App\Controllers\CategoriesController;
use App\Controllers\HomeController;
use App\Controllers\ReceiptController;
use App\Controllers\TransactionImportController;
use App\Controllers\TransactionsController;
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
        $categories->get('/load', [CategoriesController::class, 'load']);
        $categories->post('', [CategoriesController::class, 'store']);
        $categories->delete('/{category}', [CategoriesController::class, 'delete']);
        $categories->get('/{category}', [CategoriesController::class, 'get']);
        $categories->post('/{category}', [CategoriesController::class, 'update']);
    })->add(AuthMiddleware::class);

    $app->group('/transactions', function (RouteCollectorProxy $transactions) {
        $transactions->get('', [TransactionsController::class, 'index']);
        $transactions->post('', [TransactionsController::class, 'store']);
        $transactions->get('/load', [TransactionsController::class, 'load']);
        $transactions->post('/import', [TransactionImportController::class, 'import']);
        $transactions->delete('/{transaction}', [TransactionsController::class, 'delete']);
        $transactions->get('/{transaction}', [TransactionsController::class, 'get']);
        $transactions->post('/{transaction}', [TransactionsController::class, 'update']);
        $transactions->post('/{transaction}/receipts', [ReceiptController::class, 'store']);
        $transactions->get('/{transaction}/receipts/{receipt}', [ReceiptController::class, 'download']);
        $transactions->delete('/{transaction}/receipts/{receipt}', [ReceiptController::class, 'delete']);
        $transactions->post('/{transaction}/review', [TransactionsController::class, 'toggleReviewed']);
    })->add(AuthMiddleware::class);

};
