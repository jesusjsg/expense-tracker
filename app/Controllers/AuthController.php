<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\AuthInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\DataObjects\SignupUserData;
use App\Enum\AuthAttemptStatus;
use App\Exception\ValidationException;
use App\ResponseFormatter;
use App\Validators\SignupUserValidator;
use App\Validators\TwoFactorLoginValidator;
use App\Validators\UserLoginValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
 
class AuthController
{
    public function __construct(
        private readonly Twig $twig, 
        private readonly ValidatorFactoryInterface $validatorFactory, 
        private readonly AuthInterface $auth,
        private readonly ResponseFormatter $responseFormatter
    ) {
    }

    public function loginView(Response $response): Response
    {
        return $this->twig->render($response, 'auth/login.twig');
    }

    public function signupView(Response $response): Response
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

        $status = $this->auth->attemptLogin($data);

        if ($status === AuthAttemptStatus::FAILED) {
            throw new ValidationException(['password' => ['The email or password ara invalid.']]);
        }

        if ($status === AuthAttemptStatus::TWO_FACTOR_AUTH) {
            return $this->responseFormatter->json($response, ['two_factor' => true]);
        }

        return $this->responseFormatter->json($response, []);
    }

    public function logOut(Response $response): Response
    {
        $this->auth->logOut();

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function twoFactorLogin(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(TwoFactorLoginValidator::class)->validate(
            $request->getParsedBody()
        );

        if (! $this->auth->attemptTwoFactorLogin($data)) {
            throw new ValidationException(['code' => ['Invalid code']]);
        }

        return $response;
    }
}
