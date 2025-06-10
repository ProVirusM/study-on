<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    public function __construct()
    {
    }

    public function auth(array $data): array
    {
        //file_put_contents('/tmp/mock_called.txt', 'mock works');
        if ($data['username'] === 'admin@example.com' && $data['password'] === 'adminpass') {
            return [
                'token' => 'admin-token',
                'roles' => ['ROLE_SUPER_ADMIN'],
                'refresh_token'=>'admin-token'
            ];
        }

        return [
            'token' => 'mock-token',
            'roles' => ['ROLE_USER'],
            'refresh_token'=>'mock-token'
        ];
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
            'roles' => ['ROLE_USER'],
            'refresh_token'=>'mock-token'
        ];
    }

    public function getCurrentUser(string $token): array
    {
        if ($token == 'admin-token') {
            return [
                'username' => 'admin@example.com',
                'roles' => ["ROLE_SUPER_ADMIN"],
                'balance' => 9999.99
            ];
        }

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
            'username' => 'unknown@example.com',
            'roles' => ['ROLE_USER'],
            'balance' => 1000.00
        ];
    }
    public function getCourse(string $code): array
    {
        return match ($code) {
            'web-development' => [
                'code' => 'web-development',
                'type' => 'rent',
                'price' => '99.90'
            ],
            'python-basics' => [
                'code' => 'python-basics',
                'type' => 'free'
            ],
            'databases-sql' => [
                'code' => 'databases-sql',
                'type' => 'buy',
                'price' => '159.00'
            ],
            'python-basics1' => [
                'code' => 'python-basics1',
                'type' => 'free'
            ],
            default => throw new BillingUnavailableException('Курс не найден', 404),
        };
    }

    public function payCourse(string $code, string $token): array
    {
        if ($token == 'admin-token') {
            return [
                'success' => true,
                'course_type' => 'rent',
                'expires_at' => '2025-06-07T13:46:07+00:00'
            ];
        }

        throw new BillingUnavailableException('На вашем счету недостаточно средств', 406);
    }

    public function getTransactions(string $token, array $filters = []): array
    {
        $transactions = [
            [
                'id' => 11,
                'created_at' => '2019-05-20T13:46:07+00:00',
                'type' => 'payment',
                'course_code' => 'web-development',
                'amount' => '99.90'
            ],
            [
                'id' => 9,
                'created_at' => '2019-05-20T13:45:11+00:00',
                'type' => 'deposit',
                'amount' => '5000.00'
            ]
        ];

        // Применение фильтров
        if (isset($filters['type'])) {
            $transactions = array_filter($transactions, fn($tx) => $tx['type'] === $filters['type']);
        }

        if (isset($filters['course_code'])) {
            $transactions = array_filter($transactions, fn($tx) => $tx['course_code'] === $filters['course_code']);
        }

        return array_values($transactions); // Сброс ключей
    }

    public function newCourse(string $token, \App\Dto\CourseDto $course): array
    {
        return [
            'success' => true
        ];
    }

    public function editCourse(string $token, string $code, \App\Dto\CourseDto $course): array
    {
        return [
            'success' => true
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return [
            'token' => 'new-mock-token',
            'refresh_token' => 'new-refresh-token',
            'expires_at' => '2099-01-01T00:00:00+00:00'
        ];
    }

}
