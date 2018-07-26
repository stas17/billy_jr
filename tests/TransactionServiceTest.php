<?php

namespace App\Tests;

use App\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\InvalidParameterException;

class TransactionServiceTest extends TestCase
{

    /**
     * @dataProvider createPaymentTransactionWithExceptionProvider
     * @expectedException InvalidParameterException
     * @param string $message
     */
    public function testCreatePaymentTransactionWithException(string $message)
    {
        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);

        $transactionServiceMock = new TransactionService($entityManagerMock);

        $transactionServiceMock->createTransaction($message, 'payment');
    }

    public function testCreatePaymentTransaction()
    {
        /** @var EntityManager $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);

        $transactionService = new TransactionService($entityManagerMock);

        $transaction = $transactionService->createTransaction('{"order_id":"42","customer_login":"billy.jr","amount":"777"}', 'payment');

        $this->assertEquals(42, $transaction->getOrderId());
        $this->assertEquals('billy.jr', $transaction->getCustomerLogin());
        $this->assertEquals(7770000, $transaction->getAmount());
        $this->assertEquals('payment', $transaction->getType());
    }

    /**
     * @return array
     */
    public function createPaymentTransactionWithExceptionProvider(): array
    {
        return [
            ['{"order_id":"42","customer_login":"stas","amoun'],
            ['{"order_id":"42","customer_lodin":"blili.jr","amount":"32"}'],
            ['{"order_id":"42","customer_lodin":"blili.jr","amount":"32"}'],
        ];
    }
}
