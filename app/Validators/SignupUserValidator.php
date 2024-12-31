<?php

declare(strict_types = 1);

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Entity\User;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManager;
use Valitron\Validator;

class SignupUserValidator implements ValidatorInterface
{
    public function __construct(private readonly EntityManager $entityManager)
    {
        
    }

    public function validate(array $data): array
    {
        $validator = new Validator();

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

        return $data;
    }
}