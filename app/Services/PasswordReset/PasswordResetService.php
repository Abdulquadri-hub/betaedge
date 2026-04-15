<?php

namespace App\Services\PasswordReset;

use App\Contracts\Repositories\PasswordReset\PasswordResetRepositoryInterface;
use App\Contracts\Services\PasswordReset\PasswordResetServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetService implements PasswordResetServiceInterface
{
    public function __construct(
        private PasswordResetRepositoryInterface $resetRepository
    ) {}

    /**
     * Send password reset email with token
     *
     * @param string $email User email address
     * @return bool Success status
     */
    public function sendResetEmail(string $email): bool
    {
        // Check if user exists
        $user = User::where('email', $email)->first();
        if (!$user) {
            // For security, return true even if user doesn't exist
            // to prevent email enumeration
            return true;
        }

        try {
            // Generate reset token
            $token = $this->resetRepository->createToken($email);

            // Create reset URL
            $resetUrl = route('password.reset', [
                'email' => $email,
                'token' => $token,
            ]);

            // Send email (you can customize this with a mailable class)
            // For now, using a simple approach - update to use Mail::send() or Mailable class
            // Mail::send('emails.password-reset', ['url' => $resetUrl, 'user' => $user], function ($message) use ($email) {
            //     $message->to($email)->subject('Reset Your Password');
            // });

            // For development, log the reset URL
            \Log::info('Password reset token created', [
                'email' => $email,
                'reset_url' => $resetUrl,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate password reset token
     *
     * @param string $email User email address
     * @param string $token Reset token
     * @return bool Token is valid
     */
    public function validateResetToken(string $email, string $token): bool
    {
        return $this->resetRepository->verifyToken($email, $token);
    }

    /**
     * Reset user password with valid token
     *
     * @param string $email User email address
     * @param string $token Reset token
     * @param string $password New password
     * @return bool Success status
     */
    public function resetPassword(string $email, string $token, string $password): bool
    {
        // Validate token first
        if (!$this->validateResetToken($email, $token)) {
            return false;
        }

        try {
            // Find and update user
            $user = User::where('email', $email)->first();
            if (!$user) {
                return false;
            }

            // Update password
            $user->update([
                'password' => Hash::make($password),
            ]);

            // Delete the token after successful reset
            $this->resetRepository->deleteToken($email);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to reset password', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
