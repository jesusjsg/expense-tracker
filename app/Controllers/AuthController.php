<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Auth;
use App\Contracts\ValidatorFactoryInterface;
use App\DataObjects\SignupUserData;
use App\Exception\ValidationException;
use App\Validators\SignupUserValidator;
use App\Validators\UserLoginValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class AuthController
{
    public function __construct(
        private readonly Twig $twig, 
        private readonly ValidatorFactoryInterface $validatorFactory, 
        private readonly Auth $auth
    ) {
    }

    public function loginView(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'auth/login.twig');
    }

    public function signupView(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'auth/signup.twig');
    }

    public function signup(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(SignupUserValidator::class)->validate(
            $request->getParsedBody()
        );

        $this->auth->signup(
            new SignupUserData($data['name'], $data['email'], $data['password'])
        );

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logIn(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(UserLoginValidator::class)->validate(
            $request->getParsedBody()
        );

        if (! $this->auth->attemptLogin($data)) {
            throw new ValidationException(['password' => ['The email or password ara invalid.']]);
        }

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logOut(Request $request, Response $response): Response
    {
        $this->auth->logOut();

        return $response->withHeader('Location', '/')->withStatus(302);
    }
}