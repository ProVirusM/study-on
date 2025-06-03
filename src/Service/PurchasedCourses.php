<?php

namespace App\Service;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use Symfony\Bundle\SecurityBundle\Security;

class PurchasedCourses
{
    public function __construct(
        private Security $security,
        private BillingClient $billingClient,
    ) {}

    /**
     * @throws BillingUnavailableException
     */
    public function getDataCourse(Course $course): array
    {
        $billingResponse = $this->billingClient->getCourse($course->getCode());

        $courseData = [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'lessons' => $course->getLessons(),
            'type' => $billingResponse['type'] ?? 'free',
            'price' => $billingResponse['price'] ?? null,
        ];

        return array_merge($courseData, $this->checkPurchasedCourse($course->getCode(), $courseData['type']));
    }

    public function checkPurchasedCourse(string $courseCode, string $courseType): array
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return ['isPurchased' => false];
        }

        try {
            $transactions = $this->billingClient->getTransactions($user->getApiToken(), ['course_code' => $courseCode]);

            if (empty($transactions)) {
                return ['isPurchased' => false];
            }
            //dump($transactions);
            // Берем последнюю транзакцию для этого курса
            //$transaction = end($transactions);
            $transaction = reset($transactions);
            if ($courseType == 'rent') {
                // Проверяем наличие expires_at и его валидность
                if (!isset($transaction['time_arend'])) {
                    return ['isPurchased' => false];
                }

                $expiresAt = new \DateTimeImmutable($transaction['time_arend']);
                $now = new \DateTimeImmutable();

                return [
                    'isPurchased' => $expiresAt > $now,
                    'message' => $expiresAt > $now
                        ? "Арендовано до {$expiresAt->format('Y-m-d H:i')}"
                        : "Аренда истекла {$expiresAt->format('Y-m-d H:i')}",
                ];
            }

            // Для типа 'buy' просто проверяем наличие любой транзакции
            return [
                'isPurchased' => true,
                'message' => 'Куплено',
            ];

        } catch (\Exception $e) {
            // Логируем ошибку и возвращаем false
            // Можно добавить LoggerInterface в конструктор для логирования
            return ['isPurchased' => false];
        }
    }
}