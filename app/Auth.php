<?php

declare(strict_types = 1);

namespace App;

use App\Contracts\AuthInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DataObjects\SignupUserData;
use App\Mail\SignupEmail;

class Auth implements AuthInterface
{
    private ?UserInterface $user = null;

    public function __construct(
        private readonly UserProviderServiceInterface $userProvider,
        private readonly SessionInterface $session,
        private readonly SignupEmail $signupEmail
    ) {
    }

    public function user(): ?UserInterface
    {
        if ($this->user != null) {
            return $this->user;
        }

        $userId = $this->session->get('user');

        if (! $userId) {
            return null;
        }

        $user = $this->userProvider->getById($userId);
    
        if (! $user) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    public function attemptLogin(array $data): bool
    {
        $user = $this->userProvider->getByCredentials($data);

        if (! $user || ! $this->checkCreadentials($user, $data)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function checkCreadentials(UserInterface $user, array $data): bool
    {
        return password_verify($data['password'], $user->getPassword());
    }

    public function logOut(): void
    {
        $this->session->forget('user');
        $this->session->regenerateSession();
        $this->user = null;
    }

    public function signup(SignupUserData $data): UserInterface
    {
        $user = $this->userProvider->newUser($data);
        $this->login($user);

        $this->signupEmail->send($user);
        
        return $user;
    }

    public function login(UserInterface $userInterface): void
    {
        $this->session->regenerateSession();
        $this->session->put('user', $userInterface->getUserId());
        $this->user = $userInterface;
    }
}
