<?php

namespace App\Contracts\Services\PasswordReset;

interface PasswordResetServiceInterface
{
    public function sendResetEmail(string $email): bool;
    public function validateResetToken(string $email, string $token): bool;
    public function resetPassword(string $email, string $token, string $password): bool;
}
