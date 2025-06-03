<?php


namespace App\Tests\Mock;

use App\Service\JwtTokenManager;

class FakeJwtTokenManager extends JwtTokenManager
{
    public function isExpired(string $token): bool
    {
        return false; // Всегда не истёк
    }

    public function decodePublic(string $token): array
    {
        return [
            'username' => 'admin@example.com',
            'roles' => ["ROLE_SUPER_ADMIN"],
            'exp' => time() + 3600
        ];
    }
}
