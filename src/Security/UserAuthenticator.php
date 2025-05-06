<?php

namespace App\Security;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use App\Exception\BillingUnavailableException;

class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private BillingClient $billingClient
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');
        $password = $request->getPayload()->getString('password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        try {
            // Получаем токен по логину и паролю

            $authResponse = $this->billingClient->auth([
                'username' => $email,
                'password' => $password,
            ]);
//            try {
//                $authResponse = $this->billingClient->auth([
//                    'username' => $email,
//                    'password' => $password,
//                ]);
//            } catch (BillingUnavailableException $e){
//                throw new CustomUserMessageAuthenticationException('Сервер не отвечает, попробуйте позже.');
//            }
            $token = $authResponse['token'];

            // Лямбда-функция, которая подгружает пользователя по токену
            $loadUser = function (string $userIdentifier) use ($token): User {
                $userData = $this->billingClient->getCurrentUser($token);

                $user = new User();
                $user->setEmail($userData['username'])
                    ->setRoles($userData['roles'])
                    ->setApiToken($token);

                return $user;
            };

            return new SelfValidatingPassport(
                new UserBadge($token, $loadUser),
                [
                    new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                    new RememberMeBadge(),
                ]
            );
        } catch (BillingUnavailableException $exception) {
            throw new CustomUserMessageAuthenticationException('Сервис временно недоступен. Попробуйте позже.');
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Неверный email или пароль.');

        }
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Проверяем, есть ли путь для редиректа, если он сохранен в сессии
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Если нет, редиректим на главную страницу
//        return new RedirectResponse($this->urlGenerator->generate('app_login'));
        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
