<?php

declare(strict_types = 1);

namespace App\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;

class CsrfFieldsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Twig $twig, private readonly ContainerInterface $containerInterface) 
    {  
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $csrf = $this->containerInterface->get('csrf');

        $csrfNameKey  = $csrf->getTokenNameKey();
        $csrfValueKey = $csrf->getTokenValueKey();
        $csrfName     = $csrf->getTokenName();
        $csrfValue    = $csrf->getTokenValue();
        $fields       = <<<CSRF_INPUTS
        <input type="hidden" name="$csrfNameKey" value="$csrfName">
        <input type="hidden" name="$csrfValueKey" value="$csrfValue">
        CSRF_INPUTS;

        $this->twig->getEnvironment()->addGlobal('csrf', [
            'keys' => [
                'name'  => $csrfNameKey,
                'value' => $csrfValueKey
            ],
            'name'   => $csrfName,
            'value'  => $csrfValue,
            'fields' => $fields,
        ]);

        return $handler->handle($request);
    }
}