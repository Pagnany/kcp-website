<?php

namespace Pagnany\Kcp\Auth;

class Auth {
    public static function isLoggedIn(): bool {
        return isset($_COOKIE['kcp_auth_token']) && isset($_COOKIE['kcp_username']);
    }
} 