<?php

namespace App\Tests\Mock;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Dto\UserRegisterDto;
use App\Form\RegistrationFormType;
use Symfony\Component\Form\Test\TypeTestCase;
class AuthControllerTest extends WebTestCase
{
    private function createMockedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        $mock = new BillingClientMock();
        $client->getContainer()->set('App\Service\BillingClient', $mock);

        return $client;
    }

    public function testSuccessfulRegistration(): void
    {
        $client = $this->createMockedClient();

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Зарегистрироваться')->form();

        $form['registration_form[email]'] = 'new@example.com';
        $form['registration_form[password]'] = 'password123';
        $form['registration_form[confirmPassword]'] = 'password123';

        $client->submit($form);
        $this->assertResponseRedirects('/courses');
    }

    public function testProfileAccessWithValidToken(): void
    {
        $client = $this->createMockedClient();

        // Создаём и логиним пользователя
        $user = new User();
        $user->setEmail('test@example.com')
            ->setApiToken('valid-token')
            ->setRoles(['ROLE_USER']);
        $client->loginUser($user); // Аутентифицируем пользователя

        // Теперь делаем запрос
        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Профиль пользователя');
    }
//    public function testValSuccessfulRegistration(): void
//    {
//        $client = $this->createMockedClient();
//
//        $crawler = $client->request('GET', '/register');
//        $form = $crawler->selectButton('Зарегистрироваться')->form();
//
//        $form['registration_form[email]'] = 'new@example.com';
//        $form['registration_form[password]'] = 'password123';
//        $form['registration_form[confirmPassword]'] = 'password123';
//
//        $client->submit($form);
//        //$errors = $form->getErrors(true);
//        $errors = $form->disableValidation(true);
//        $this->assertCount(3, $errors);
//
//    }
//    public function testFormValidation(): void
//    {
//        $formData = [
//            'email' => 'invalid-email',
//            'password' => 'short',
//            'confirmPassword' => 'different',
//        ];
//
//        $form = $this->factory->create(RegistrationFormType::class);
//
//        $form->submit($formData);
//
//        $this->assertFalse($form->isValid());
//        $errors = $form->getErrors(true);
//        $this->assertCount(3, $errors);
//    }

}
