<?php

declare(strict_types = 1);

namespace App\Middleware;

use App\Contracts\SessionInterface;
use App\Exception\ValidationException;
use App\Services\RequestService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidationExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly SessionInterface $session,
        private readonly RequestService $requestService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch(ValidationException $error) {
            $response = $this->responseFactory->createResponse();
            $referer  = $this->requestService->getReferer($request);
            $oldData = $request->getParsedBody();

            $excludeFields = ['password', 'confirmPassword'];

            $this->session->flash('errors', $error->errors);
            $this->session->flash('old', array_diff_key($oldData, array_flip($excludeFields)));

            return $response->withHeader('Location', $referer)->withStatus(302);
        }
    }
}