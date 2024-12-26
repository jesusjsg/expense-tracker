<?php

declare(strict_types=1);

use App\Config;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Symfony\Component\Console\Application;

$app       = require_once 'bootstrap.php';
$container = $app->getContainer();
$config    = $container->get(Config::class);

$entityManager     = $container->get(EntityManager::class);
$dependencyFactory = DependencyFactory::fromEntityManager(
    new PhpFile(CONFIG_PATH . '/migrations.php'),
    new ExistingEntityManager($entityManager)
);

$migrationCommands = require_once CONFIG_PATH . '/commands/migration_commands.php';
$customCommands    = require_once CONFIG_PATH . '/commands/commands.php';

$cliApp = new Application($config->get('app_name'), $config->get('app_version'));

ConsoleRunner::addCommands($cliApp, new SingleManagerProvider($entityManager));

$cliApp->addCommands($migrationCommands($dependencyFactory));
$cliApp->addCommands(array_map(fn($command) => $container->get($command), $customCommands));

$cliApp->run();