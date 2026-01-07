<?php

namespace App\Core;

class SessionManager
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->set('csrf_token', $token);
        return $token;
    }

    public function validateCsrfToken(string $token): bool
    {
        return $this->has('csrf_token') && hash_equals($this->get('csrf_token'), $token);
    }
}
