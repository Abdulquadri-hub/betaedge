<?php

namespace App\Contracts\Repositories\PasswordReset;

interface PasswordResetRepositoryInterface
{
    public function createToken(string $email): string;
    public function verifyToken(string $email, string $token): bool;
    public function deleteToken(string $email): bool;
    public function deleteExpiredTokens(int $minutes = 60): int;
}
