<?php

declare(strict_types = 1);

namespace App\Contracts;

interface ValidatorFactoryInterface
{
    public function make(string $class): ValidatorInterface;
}