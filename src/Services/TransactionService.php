<?php

namespace App\Services;

use App\Entity\Transaction;
use App\Exception\ConfirmHoldException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Exception\InvalidParameterException;

class TransactionService
{
    /** @var int */
    private $exponent = 10000;

    /** @var array */
    private $requiredFields = [
        'payment' => ['customer_login', 'amount', 'order_id'],
        'payout' => ['customer_login', 'amount', 'order_id'],
        'hold' => ['customer_login', 'amount', 'order_id'],
        'transfer' => ['customer_login_to', 'customer_login_from', 'amount', 'order_id'],
        'confirm' => ['customer_login', 'order_id'],
        'unhold' => ['customer_login', 'order_id'],
    ];

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
     * @param string $message
     * @param string $type
     * @return Transaction
     * @throws InvalidParameterException
     */
    public function createTransaction(string $message, string $type): Transaction
    {
        $this->validateMessage($message, $type);
        $message = \json_decode($message, true);

        $amount = (float)($message['amount'] ?? 0);
        return (new Transaction())
            ->setCustomerLogin($message['customer_login'])
            ->setAmount(($this->amountConvertToOrder($amount)))
            ->setOrderId($message['order_id'])
            ->setType($type);
    }

    /**
     * @param string $message
     * @return Transaction
     * @throws InvalidParameterException
     */
    public function createToTransferTransaction(string $message)
    {
        $this->validateMessage($message, 'transfer');
        $message = \json_decode($message, true);

        $amount = (float)($message['amount'] ?? 0);
        return (new Transaction())
            ->setCustomerLogin($message['customer_login_to'])
            ->setAmount(($this->amountConvertToOrder($amount)))
            ->setOrderId($message['order_id'])
            ->setType('payment');
    }


    /**
     * @param string $message
     * @return Transaction
     * @throws InvalidParameterException
     */
    public function createFromTransferTransaction(string $message)
    {
        $this->validateMessage($message, 'transfer');
        $message = \json_decode($message, true);

        $amount = (float)($message['amount'] ?? 0);
        return (new Transaction())
            ->setCustomerLogin($message['customer_login_from'])
            ->setAmount(($this->amountConvertToOrder($amount)))
            ->setOrderId($message['order_id'])
            ->setType('payout');
    }

    /**
     * @param Transaction $transaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws UniqueConstraintViolationException
     */
    public function saveTransaction(Transaction $transaction)
    {
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    /**
     * @param Transaction $transaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws UniqueConstraintViolationException
     * @throws ConfirmHoldException
     */
    public function confirmHoldTransaction(Transaction $transaction)
    {
        $this->checkUnholdHoldTransactionNotExists($transaction);
        $this->checkHoldTransactionExists($transaction);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    /**
     * @param Transaction $transaction
     * @throws ConfirmHoldException
     */
    public function checkHoldTransactionExists(Transaction $transaction)
    {
        /** @var Transaction $balance */
        $unholdTransaction = $this->entityManager->getRepository(Transaction::class)->findOneBy(
            ['type' => 'hold', 'order_id' => $transaction->getOrderId()]
        );
        if (null === $unholdTransaction) {
            throw new  ConfirmHoldException('hold transaction not found');
        }
    }

    /**
     * @param Transaction $transaction
     * @throws ConfirmHoldException
     */
    public function checkConfirmHoldTransactionNotExists(Transaction $transaction)
    {
        /** @var Transaction $balance */
        $unholdTransaction = $this->entityManager->getRepository(Transaction::class)->findOneBy(
            ['type' => 'confirm', 'order_id' => $transaction->getOrderId()]
        );
        if (null !== $unholdTransaction) {
            throw new  ConfirmHoldException('already confirmed');
        }
    }

    /**
     * @param Transaction $transaction
     * @throws ConfirmHoldException
     */
    public function checkUnholdHoldTransactionNotExists(Transaction $transaction)
    {
        /** @var Transaction $balance */
        $unholdTransaction = $this->entityManager->getRepository(Transaction::class)->findOneBy(
            ['type' => 'unhold', 'order_id' => $transaction->getOrderId()]
        );
        if (null !== $unholdTransaction) {
            throw new  ConfirmHoldException('already unholded');
        }
    }


    /**
     * @param float $amount
     *
     * @return int
     */
    private function amountConvertToOrder(float $amount): int
    {
        return (int)($amount * $this->exponent);
    }

    /**
     * @param string $message
     * @param string $type
     *
     * @throws InvalidParameterException
     */
    private function validateMessage(string $message, string $type): void
    {
        $message = \json_decode($message, true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidParameterException();
        }
        \array_map(function ($field) use ($message) {
            if (!\array_key_exists($field, $message)) {
                throw new InvalidParameterException();
            }
        }, $this->requiredFields[$type]);
    }
}
