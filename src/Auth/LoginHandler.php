<?php

namespace Pagnany\Kcp\Auth;

class LoginHandler
{
    private const COOKIE_TOKEN_NAME = 'kcp_auth_token';
    private const COOKIE_USERNAME_NAME = 'kcp_username';
    private const COOKIE_EXPIRY = 86400 * 30; // 30 days

    /**
     * Process login attempt
     *
     * @param string $username
     * @param string $password
     * @return array{success: bool, message: string}
     */
    public function processLogin(string $username, string $password): array
    {
        // TODO: Replace with proper authentication logic
        if ($username === 'admin' && $password === 'password') {
            $this->setAuthCookies($username);
            return [
                'success' => true,
                'message' => 'Login successful!'
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid username or password.'
        ];
    }

    /**
     * Set authentication cookies
     *
     * @param string $username
     * @return void
     */
    private function setAuthCookies(string $username): void
    {
        $token = $this->generateSecureToken();
        
        // Set cookies with secure parameters
        setcookie(
            self::COOKIE_TOKEN_NAME,
            $token,
            [
                'expires' => time() + self::COOKIE_EXPIRY,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );

        setcookie(
            self::COOKIE_USERNAME_NAME,
            $username,
            [
                'expires' => time() + self::COOKIE_EXPIRY,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * Generate a secure random token
     *
     * @return string
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }
} 
