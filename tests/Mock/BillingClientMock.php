<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    public function __construct()
    {
    }

    public function register(array $credentials): array
    {
        if ($credentials['username'] === 'existing@example.com') {
            return [
                'code' => 400,
                'message' => 'Пользователь уже существует'
            ];
        }

        return [
            'token' => 'mock-token',
            'roles' => ['ROLE_USER']
        ];
    }

    public function getCurrentUser(string $token): array
    {
        if ($token === 'valid-token') {
            return [
                'username' => 'test@example.com',
                'roles' => ['ROLE_USER'],
                'balance' => 1000.00
            ];
        }
        if ($token === 'expired') {
            throw new \Exception('token expired');
        }

        return [
            'username' => 'test@example.com',
            'roles' => ['ROLE_USER'],
            'balance' => 1000.00
        ];
    }
}
