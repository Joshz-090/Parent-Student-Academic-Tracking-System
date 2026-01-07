<?php

namespace App\Auth;

use App\Core\SessionManager;

class AuthMiddleware
{
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function requireRole(string $role): void
    {
        $user = $this->session->get('user');

        if (!$user || $user['role'] !== $role) {
            header('Location: /login.php');
            exit;
        }
    }

    public function getCurrentUser(): ?array
    {
        return $this->session->get('user');
    }
}
