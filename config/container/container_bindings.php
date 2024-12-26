<?php

declare(strict_types = 1);

use App\Config;
use App\Enum\AppEnvironment;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollection;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;

use function DI\create;

return [
    Config::class          => create(Config::class)->constructor(require CONFIG_PATH . '/app.php'),
    EntityManager::class   => function (Config $config) {
        $connectionParams = $config->get('doctrine.connection');

        $connection = DriverManager::getConnection($connectionParams);

        $config = ORMSetup::createAttributeMetadataConfiguration(
            $config->get('doctrine.entity_dir'),
            $config->get('doctrine.dev_mode')
        );
        $entityManager = new EntityManager($connection, $config);
        return $entityManager;
    },
        
    Twig::class => function (Config $config, ContainerInterface $container) {
        $twig = Twig::create(VIEW_PATH, [
            'cache'         => STORAGE_PATH . '/cache/templates',
            'auto_reload'   => AppEnvironment::isDevelopment($config->get('app_environment')),
        ]);

        $twig->addExtension(new IntlExtension());
        $twig->addExtension(new EntryFilesTwigExtension($container));
        $twig->addExtension(new AssetExtension($container->get('webpack_encore.packages')));
        $twig->addExtension(new DebugExtension());
        
        return $twig;
    },

    /* The following two bindings are needed for EntryFilesTwigExtension and AssetExtension to work for Twig */

    'webpack_encore.entrypoint_lookup_collection' => static function (): EntrypointLookupCollectionInterface {
        $entrypointLookup = new EntrypointLookup(BUILD_PATH . '/entrypoints.json');
        $serviceLocator =  new ServiceLocator(['_default' => function () use ($entrypointLookup) {
            return $entrypointLookup;
        }]);
        return new EntrypointLookupCollection($serviceLocator);
    },

    'webpack_encore.packages' => fn() => new Packages(
        new Package(new JsonManifestVersionStrategy(BUILD_PATH . '/manifest.json'))
    ),
    'webpack_encore.tag_renderer' => fn(ContainerInterface $container) => new TagRenderer(
        $container->get('webpack_encore.entrypoint_lookup_collection'),
        $container->get('webpack_encore.packages')
    )
];