<?php

namespace App\Consumer;

use App\Services\BalanceService;
use App\Services\EventService;
use App\Services\TransactionService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class UnholdConsumer implements ConsumerInterface
{
//php bin/console billy:test-unhold billy-jr 334334
//php bin/console rabbitmq:consumer unhold

    /** @var TransactionService */
    private $transactionService;
    /** @var BalanceService */
    private $balanceService;
    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var EventService */
    private $eventService;

    /**
     * @param EntityManager $entityManager
     * @param LoggerInterface $logger
     * @param EventService $eventService
     * @param TransactionService $transactionService
     * @param BalanceService $balanceService
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        EventService $eventService,
        TransactionService $transactionService,
        BalanceService $balanceService
    ) {
        $this->transactionService = $transactionService;
        $this->balanceService = $balanceService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->eventService = $eventService;
    }

    /**
     * @param AMQPMessage $message
     */
    public function execute(AMQPMessage $message): void
    {
        $this->logger->info($message->getBody());

        try {
            $transaction = $this->transactionService->createTransaction($message->getBody(), 'unhold');
        } catch (InvalidParameterException $e) {
            $this->logger->error($e->getMessage(), ['message' => $message->getBody()]);

            return;
        } catch (\Throwable $e) {
            $this->logger->emergency($e->getMessage(), ['message' => $message->getBody()]);

            return;
        }

        $this->entityManager->beginTransaction();
        try {
            //Проверим сущесотвование холда.
            //Отсутствие возврата.
            //И вернем деньги
            $balance = $this->balanceService->getUserBalance($transaction->getCustomerLogin());
            $this->transactionService->checkConfirmHoldTransactionNotExists($transaction);
            $this->transactionService->checkHoldTransactionExists($transaction);
            $this->transactionService->saveTransaction($transaction);
            $this->balanceService->updateBalance($balance, $transaction);
            $this->eventService->raiseSuccess($transaction);
        } catch (UniqueConstraintViolationException|InvalidTypeException|ResourceNotFoundException|ConfirmHoldException $e) {
            $this->entityManager->rollback();

            $this->logger->error($e->getMessage());
            $this->eventService->raiseError($transaction, $e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->emergency($e->getMessage());
            $this->eventService->raiseError($transaction, $e->getMessage());

            return;
        }

        $this->entityManager->commit();
    }
}