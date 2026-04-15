<?php

namespace App\Repositories\PasswordReset;

use App\Contracts\Repositories\PasswordReset\PasswordResetRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetRepository implements PasswordResetRepositoryInterface
{
    /**
     * Create a password reset token for a user
     *
     * @param string $email
     * @return string Generated token
     */
    public function createToken(string $email): string
    {
        // Delete any existing tokens for this email
        $this->deleteToken($email);

        // Generate a secure token
        $token = Str::random(60);

        // Store the token
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => hash('sha256', $token),
            'created_at' => now(),
        ]);

        return $token;
    }

    /**
     * Verify and retrieve the password reset token
     *
     * @param string $email
     * @param string $token
     * @return bool Token is valid
     */
    public function verifyToken(string $email, string $token): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return false;
        }

        // Check if token is expired (60 minutes)
        if ($record->created_at && now()->diffInMinutes($record->created_at) > 60) {
            $this->deleteToken($email);
            return false;
        }

        // Verify the hashed token matches
        return hash_equals(hash('sha256', $token), $record->token);
    }

    /**
     * Delete a password reset token
     *
     * @param string $email
     * @return bool Success status
     */
    public function deleteToken(string $email): bool
    {
        return DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete() > 0;
    }

    /**
     * Delete expired tokens older than specified minutes
     *
     * @param int $minutes Minutes to consider as expired (default 60)
     * @return int Number of tokens deleted
     */
    public function deleteExpiredTokens(int $minutes = 60): int
    {
        return DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->delete();
    }
}
