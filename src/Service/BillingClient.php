<?php
namespace App\Service;
use App\Exception\BillingUnavailableException;
class BillingClient
{
    private string $billingUrl;
    public function __construct(
        string $billingUrl,
    )
    {
        $this->billingUrl = $billingUrl;
    }

    /**
     * @throws BillingUnavailableException
     * @throws \Exception
     */
    public function request(
        string $method = 'GET',
        string $url = null,
        array $data = [],
        array $headers = [],
        string $token = '',
    ):array
    {
        $headers[] = 'Authorization:Bearer ' . $token;
        $headers[] = 'Content-type:application/json';
        $curlOptions = [
            CURLOPT_URL => $this->billingUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($method === 'POST') {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        try {
            $curl = curl_init();
            curl_setopt_array($curl, $curlOptions);
            $response = curl_exec($curl);
            //curl_close($curl);
        } catch (\Exception $exception){
            throw new \Exception('Ошибка на стороне сервера');
        }
        if (curl_errno($curl)) {
            curl_close($curl);
            throw new BillingUnavailableException('Сервис времменно не доступен. Попробуйте позже.', 6);
        }

        curl_close($curl);
        return json_decode($response, true);
    }
    public function auth(array $data): array
    {
        return $this->request(
            method: 'POST',
            url: '/api/v1/auth',
            data: $data,
        );
    }

    public function register(array $data): array
    {
        return $this->request(
            method: 'POST',
            url: '/api/v1/register',
            data: $data
        );
    }

    public function getCurrentUser(string $token): array
    {
        return $this->request(
            url: '/api/v1/users/current',
            token: $token,
        );
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->request(
            method: 'POST',
            url: '/api/v1/token/refresh',
            data: [
                'refresh_token' => $refreshToken,
            ],
        );
    }
    public function getCourses(): array
    {
        return $this->request(
            //method: 'GET',
            url: '/api/v1/courses'
        );
    }


    public function getCourse(string $code): array
    {
        return $this->request(
            //method: 'GET',
            url: '/api/v1/courses/' . urlencode($code)
        );
    }


    public function payCourse(string $code, string $token): array
    {
        return $this->request(
            method: 'POST',
            url: '/api/v1/courses/' . urlencode($code) . '/pay',
            token: $token
        );
    }


    public function getTransactions(string $token, array $filters = []): array
    {
        $queryParams = [];

        foreach ($filters as $key => $value) {
            $queryParams[] = sprintf('filter[%s]=%s', urlencode($key), urlencode($value));
        }

        $queryString = count($queryParams) ? '?' . implode('&', $queryParams) : '';

        return $this->request(
            //method: 'GET',
            url: '/api/v1/transactions' . $queryString,
            token: $token
        );
    }
}