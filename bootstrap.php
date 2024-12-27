<?php

declare(strict_types = 1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/path_constants.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

return require_once CONFIG_PATH . '/container/container.php';