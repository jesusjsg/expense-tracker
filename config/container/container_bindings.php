<?php

declare(strict_types = 1);

use App\Auth;
use App\Config;
use App\Contracts\AuthInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\Csrf;
use App\DataObjects\SessionConfig;
use App\Enum\AppEnvironment;
use App\Enum\SameSite;
use App\Services\UserProviderService;
use App\Session;
use App\Validators\ValidatorFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Csrf\Guard;
use Slim\Factory\AppFactory;
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
    App::class => function(ContainerInterface $container) {
        AppFactory::setContainer($container);

        $addMiddleware = require_once CONFIG_PATH . '/middleware.php';
        $router        = require_once CONFIG_PATH . '/routes/web.php';

        $app = AppFactory::create();

        $router($app);
        $addMiddleware($app);

        return $app;
    },

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
    ),

    ResponseFactoryInterface::class => fn(App $app) => $app->getResponseFactory(),
    AuthInterface::class => fn(ContainerInterface $containerInterface) => $containerInterface->get(Auth::class),
    UserProviderServiceInterface::class => fn(ContainerInterface $containerInterface) => $containerInterface->get(UserProviderService::class),
    SessionInterface::class => fn(Config $config) => new Session(
        new SessionConfig(
            $config->get('session.name', ''),
            $config->get('session.flash_name', 'flash'),
            $config->get('session.secure', true),
            $config->get('session.httponly', true),
            SameSite::from($config->get('session.sameSite', 'lax'))
        )
    ),
    ValidatorFactoryInterface::class => fn(ContainerInterface $containerInterface) => $containerInterface->get(ValidatorFactory::class),

    //Slim Csrf
    
    'csrf' => fn(ResponseFactoryInterface $responseFactoryInterface, Csrf $csrf) => new Guard(
      $responseFactoryInterface, failureHandler: $csrf->failureHandler(), persistentTokenMode: true
    )

];
