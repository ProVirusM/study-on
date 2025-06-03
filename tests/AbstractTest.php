<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\BillingClient;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Mock\BillingClientMock;

abstract class AbstractTest extends WebTestCase
{

    protected $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->loadFixtures($this->getFixtures());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

//    protected function replaceServiceBillingClient($ex = false): void
//    {
//        $this->client->disableReboot();
//        $this->client->getContainer()->set(
//            'App\Service\BillingClient',
//            new BillingClientMock('', ex: $ex),
//        );
//    }

    protected function getFixtures(): array
    {
        return [];
    }

    protected function loadFixtures(array $fixtures): void
    {
        $loader = new Loader;
        foreach ($fixtures as $fixture) {
            if (!is_object($fixture)) {
                $fixture = new $fixture;
            }
            $loader->addFixture($fixture);
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }
}
