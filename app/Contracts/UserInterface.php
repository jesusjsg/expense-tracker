<?php

declare(strict_types = 1);

namespace App\Contracts;


interface UserInterface
{
    public function getUserId(): int;
    public function getPassword(): string;
}