<?php
// src/Controller/ProfileController.php

namespace App\Controller;

use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(BillingClient $billingClient): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $billingData = $billingClient->getCurrentUser($user->getApiToken());

            if (!isset($billingData['balance'])) {
                throw new \RuntimeException('Не удалось получить данные баланса');
            }

            return $this->render('profile/index.html.twig', [
                'user' => $user,
                'balance' => $billingData['balance'],
                'is_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', $this->getProfileErrorMessage($e));
            return $this->redirectToRoute('app_login');
        }
    }

    private function getProfileErrorMessage(\Exception $e): string
    {
        return match (true) {
            str_contains($e->getMessage(), 'balance') => 'Ошибка получения баланса',
            str_contains($e->getMessage(), 'token') => 'Ошибка авторизации',
            default => 'Ошибка загрузки профиля',
        };
    }
}