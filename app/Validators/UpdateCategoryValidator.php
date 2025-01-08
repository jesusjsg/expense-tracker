<?php

declare(strict_types = 1);

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class UpdateCategoryValidator implements ValidatorInterface
{
    public function validate(array $data): array
    {
        $validator = new Validator($data);

        $validator->rule('required', ['name', 'id']);
        $validator->rule('lengthMax', 'name', 30);
        $validator->rule('integer', 'id');

        if (! $validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        return $data;
    }
}
