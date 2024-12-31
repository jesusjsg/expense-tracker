<?php

declare(strict_types = 1);

namespace App\Contracts;

use App\DataObjects\SignupUserData;

interface AuthInterface
{
    public function user(): ?UserInterface;

    public function attemptLogin(array $data): bool;

    public function checkCreadentials(UserInterface $userInterface, array $data): bool;

    public function logOut(): void;

    public function signup(SignupUserData $data): UserInterface;

    public function login(UserInterface $userInterface): void;
}