<?php

declare(strict_types = 1);

namespace App\Validators;

use App\Contracts\ValidatorFactoryInterface;
use App\Contracts\ValidatorInterface;
use Psr\Container\ContainerInterface;

class ValidatorFactory implements ValidatorFactoryInterface
{
    public function __construct(private readonly ContainerInterface $containerInterface)
    {
    }

    public function make(string $class): ValidatorInterface
    {
        $validator = $this->containerInterface->get($class);

        if ($validator instanceof ValidatorInterface) {
            return $validator;
        }

        throw new \RuntimeException('Failed to instatieate the request validator class "' . $class . '"');
    }
}