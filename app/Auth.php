<?php

declare(strict_types = 1);

namespace App;

use App\Contracts\AuthInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DataObjects\SignupUserData;
use App\Enum\AuthAttemptStatus;
use App\Mail\SignupEmail;
use App\Mail\TwoFactorAuthEmail;
use App\Services\UserLoginCodeService;

class Auth implements AuthInterface
{
    private ?UserInterface $user = null;

    public function __construct(
        private readonly UserProviderServiceInterface $userProvider,
        private readonly SessionInterface $session,
        private readonly SignupEmail $signupEmail,
        private readonly TwoFactorAuthEmail $twoFactorAuthEmail,
        private readonly UserLoginCodeService $userLoginCodeService
    ) {
    }

    public function user(): ?UserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $this->session->get('user');

        if (! $userId) {
            return null;
        }

        $user = $this->userProvider->getById($userId);
    
        if (! $user) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    public function attemptLogin(array $data): AuthAttemptStatus
    {
        $user = $this->userProvider->getByCredentials($data);

        if (! $user || ! $this->checkCreadentials($user, $data)) {
            return AuthAttemptStatus::FAILED;
        }

        if ($user->hasTwoFactorAuthEnabled()) {
            $this->startLoginWith2FA($user);

            return AuthAttemptStatus::TWO_FACTOR_AUTH;
        }

        $this->login($user);

        return AuthAttemptStatus::SUCCESS;
    }

    public function checkCreadentials(UserInterface $user, array $data): bool
    {
        return password_verify($data['password'], $user->getPassword());
    }

    public function logOut(): void
    {
        $this->session->forget('user');
        $this->session->regenerateSession();
        $this->user = null;
    }

    public function signup(SignupUserData $data): UserInterface
    {
        $user = $this->userProvider->newUser($data);
        $this->login($user);

        $this->signupEmail->send($user);
        
        return $user;
    }

    public function login(UserInterface $user): void
    {
        $this->session->regenerateSession();
        $this->session->put('user', $user->getUserId());
        $this->user = $user;
    }

    public function startLoginWith2FA(UserInterface $user): void
    {
        $this->session->regenerateSession();
        $this->session->put('2fa', $user->getUserId());
        $this->twoFactorAuthEmail->send($this->userLoginCodeService->generate($user));
    }

    public function attemptTwoFactorLogin(array $data): bool
    {
        $userId = $this->session->get('2fa');

        if (! $userId) {
            return false;
        }

        $user = $this->userProvider->getById($userId);

        if (! $user || $user->getEmail() !== $data['email']) {
            return false;
        }

        if (! $this->userLoginCodeService->verify($user, $data['code'])) {
            return false;
        }

        $this->session->forget('2fa');

        $this->logIn($user);

        $this->userLoginCodeService->deactivateAllActiveCodes($user);

        return true;
    }
}
