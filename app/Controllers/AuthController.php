<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Auth;
use App\Entity\User;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;

class AuthController
{
    public function __construct(private readonly Twig $twig, private readonly EntityManager $entityManager, private readonly Auth $auth)
    {
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
        $data = $request->getParsedBody();

        $validator = new Validator($data);
        $validator->rule('required', ['name', 'email', 'password', 'confirmPassword']);
        $validator->rule('email', 'email');
        $validator->rule('equals', 'confirmPassword', 'password')->label('Confirm password');
        $validator->rule(
            fn($field, $value, $params, $fields) => ! $this->entityManager->getRepository(User::class)->count(
                ['email' => $value]
            ),
            'email'
        )->message('That email is taken. Try another email.');

        
        if (! $validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        $user = new User();

        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $response;
    }

    public function logIn(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $validator = new Validator($data);
        $validator->rule('required', ['email', 'password']);
        $validator->rule('email', 'email');

        if (! $this->auth->attemptLogin($data)) {
            throw new ValidationException(['password' => ['The email or password ara invalid.']]);
        };

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logOut(Request $request, Response $response): Response
    {
        $this->auth->logOut();

        return $response->withHeader('Location', '/')->withStatus(302);
    }
}