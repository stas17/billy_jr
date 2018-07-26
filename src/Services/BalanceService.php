<?php

namespace App\Services;

use App\Entity\Balance;
use App\Entity\Transaction;
use App\Exception\InsufficientFundsException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class BalanceService
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $customerLogin
     * @return Balance
     * @throws  ResourceNotFoundException
     */
    public function getUserBalance(string $customerLogin): Balance
    {
        /** @var Balance $balance */
        $balance = $this->entityManager->getRepository(Balance::class)->findOneBy(
            ['customer_login' => $customerLogin]
        );

        if (null === $balance) {
            throw new ResourceNotFoundException("The customer {$customerLogin} does not have a balance");
        }

        return $balance;
    }

    /**
     * @param Balance $balance
     * @param Transaction $transaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws InvalidTypeException
     * @throws InsufficientFundsException
     */
    public function updateBalance(Balance $balance, Transaction $transaction)
    {
        switch ($transaction->getType()) {
            case 'payment':
            case 'unhold':
                $balance->setBalance($balance->getBalance() + $transaction->getAmount());

                break;
            case 'payout':
            case 'hold':
                if ($transaction->getAmount() > $balance->getBalance()) {
                    throw new InsufficientFundsException('Insufficient funds');
                }
                $balance->setBalance($balance->getBalance() - $transaction->getAmount());

                break;
            default:
                throw new InvalidTypeException();
        }

        $this->entityManager->persist($balance);
        $this->entityManager->flush();
    }
}
