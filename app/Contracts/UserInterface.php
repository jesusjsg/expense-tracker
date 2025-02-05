<?php

declare(strict_types = 1);

namespace App\Contracts;


interface UserInterface
{
    public function getUserId(): int;
    public function getPassword(): string;
    public function getName(): string;
    public function setVerifiedAt(\Datetime $verifiedAt): static;
    public function hasTwoFactorAuthEnabled(): bool;
}
