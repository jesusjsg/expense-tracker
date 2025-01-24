<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DataObjects\SignupUserData;
use App\Entity\User;

class UserProviderService implements UserProviderServiceInterface
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager)
    {
    }

    public function getById(int $userId): ?UserInterface
    {
        return $this->entityManager->find(User::class, $userId);
    }

    public function getByCredentials(array $data): ?UserInterface
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
    }

    public function newUser(SignupUserData $data): UserInterface
    {
        $user = new User();

        $user->setName($data->name);
        $user->setEmail($data->email);
        $user->setPassword(password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]));

        $this->entityManager->sync($user);

        return $user;
    }

}
