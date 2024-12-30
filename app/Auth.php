<?php

declare(strict_types = 1);

namespace App;

use App\Contracts\AuthInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;

class Auth implements AuthInterface
{
    private ?UserInterface $user = null;

    public function __construct(private readonly UserProviderServiceInterface $userProvider)
    {
    }

    public function user(): ?UserInterface
    {
        if ($this->user != null) {
            return $this->user;
        }

        $userId = $_SESSION['user'] ?? null;

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

        session_regenerate_id();

        $_SESSION['user'] = $user->getUserId();
        $this->user = $user;
        return true;
    }

    public function checkCreadentials(UserInterface $user, array $data): bool
    {
        return password_verify($data['password'], $user->getPassword());
    }

    public function logOut(): void
    {
        unset($_SESSION['user']);
        $this->user = null;
    }
}