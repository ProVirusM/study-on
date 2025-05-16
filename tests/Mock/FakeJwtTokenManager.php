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
            'username' => 'test@example.com',
            'roles' => ['ROLE_USER'],
            'exp' => time() + 3600
        ];
    }
}
