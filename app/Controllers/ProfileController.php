<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\ValidatorFactoryInterface;
use App\DataObjects\UserProfileData;
use App\Services\UserProfileService;
use App\Validators\UpdateProfileValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class ProfileController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly ValidatorFactoryInterface $validatorFactoryInterface,
        private readonly UserProfileService $userProfileService
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->twig->render(
            $response,
            'profile/index.twig',
            ['profileData' => $this->userProfileService->get($request->getAttribute('user')->getUserId())]
        );
    }

    public function update(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $this->validatorFactoryInterface->make(UpdateProfileValidator::class)->validate(
            $request->getParsedBody()
        );

        $this->userProfileService->update(
            $user,
            new UserProfileData($user->getEmail(), $data['name'], (bool) ($data['twoFactor'] ?? false))
        );

        return $response;
    }
}
