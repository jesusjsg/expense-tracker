<?php

declare(strict_types = 1);

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class UserLoginValidator implements ValidatorInterface
{
    public function validate(array $data): array
    {
        $validator = new Validator($data);
        $validator->rule('required', ['email', 'password']);
        $validator->rule('email', 'email');

        if (! $validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        return $data;
    }
}