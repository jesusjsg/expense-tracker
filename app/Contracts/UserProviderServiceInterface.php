<?php

declare(strict_types = 1);

namespace App\Contracts;

use App\DataObjects\SignupUserData;

interface UserProviderServiceInterface
{
    public function getById(int $userId): ?UserInterface;

    public function getByCredentials(array $data): ?UserInterface;

    public function newUser(SignupUserData $data): UserInterface;
}