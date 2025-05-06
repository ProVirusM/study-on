<?php

namespace App\Controller;

use App\Dto\UserRegisterDto;
use App\Exception\BillingUnavailableException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use App\Service\BillingClient;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\User;

class RegistrationController extends AbstractController
{
    public function __construct(
        private BillingClient $billingClient,
        private UserAuthenticatorInterface $userAuthenticator,
        private UserAuthenticator $billingAuthenticator,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        $user = new UserRegisterDto();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $responseBilling = $this->billingClient->register([
                    'username' => $user->email,
                    'password' => $user->password,
                ]);

                if (isset($responseBilling['token'])) {
                    $userNew = new User();
                    $userNew->setEmail($user->email)
                        ->setApiToken($responseBilling['token']);

                    // Проверка наличия роли в ответе от API
                    $roles = isset($responseBilling['roles']) ? $responseBilling['roles'] : ['ROLE_USER']; // Устанавливаем роль по умолчанию, если роли нет в ответе
                    $userNew->setRoles($roles);

                    $this->userAuthenticator->authenticateUser(
                        $userNew,
                        $this->billingAuthenticator,
                        $request
                    );
                    return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
                } else if (isset($responseBilling['code']) && $responseBilling['code'] === 400) {
                    $error = $responseBilling['message'];
                }
            } catch (BillingUnavailableException $e) {
                $error = 'Сервис временно недоступен. Попробуйте зарегистироваться позже.';
            }
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
            'user' => $user,
            'error' => $error,
        ]);
    }
}