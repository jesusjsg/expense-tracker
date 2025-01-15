<?php

declare(strict_types = 1);

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exception\ValidationException;
use App\Services\CategoryService;
use Valitron\Validator;

class TransactionValidator implements ValidatorInterface
{
    public function __construct(protected readonly CategoryService $categoryService)
    {
    }

    public function validate(array $data): array
    {
        $validator = new Validator($data);

        $validator->rule('required', ['description', 'amount', 'date', 'category']);
        $validator->rule('lengthMax', 'description', 190);
        $validator->rule('dateFormat', 'dateFormat', 'm/d/Y g:i A');
        $validator->rule('numeric', 'amount');
        $validator->rule('integer', 'category');
        $validator->rule(
            function ($field, $value, $params, $fields) use (&$data) {
                $id = (int) $value;

                if (! $id) {
                    return false;
                }

                $categoryId = $this->categoryService->getById($id);

                if ($categoryId) {
                    $data['category'] = $categoryId;

                    return true;
                }
            },
            'category'
        )->message('Category not found');

        if (! $validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        return $data;
    }
}
