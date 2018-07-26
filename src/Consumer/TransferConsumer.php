<?php

namespace App\Consumer;

use App\Exception\InsufficientFundsException;
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

class TransferConsumer implements ConsumerInterface
{
//php bin/console billy:test-transfer billy-mom billy-jr 432 334334
//php bin/console rabbitmq:consumer transfer

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
            $transactionTo = $this->transactionService->createToTransferTransaction($message->getBody());
            $transactionFrom = $this->transactionService->createFromTransferTransaction($message->getBody());
        } catch (InvalidParameterException $e) {
            $this->logger->error($e->getMessage(), ['message' => $message->getBody()]);

            return;
        } catch (\Throwable $e) {
            $this->logger->emergency($e->getMessage(), ['message' => $message->getBody()]);

            return;
        }

        $this->entityManager->beginTransaction();
        try {
            // Тут списание и пополнение в одном лице
            // Сначала полный круг списания
            $balanceFromCustomer = $this->balanceService->getUserBalance($transactionFrom->getCustomerLogin());
            $this->balanceService->updateBalance($balanceFromCustomer, $transactionFrom);
            $this->transactionService->saveTransaction($transactionFrom);

            // А теперь пополнения
            $balanceToCustomer = $this->balanceService->getUserBalance($transactionTo->getCustomerLogin());
            $this->balanceService->updateBalance($balanceToCustomer, $transactionTo);
            $this->transactionService->saveTransaction($transactionTo);

            $this->eventService->raiseTransferSuccess($transactionFrom, $transactionTo);
        } catch (UniqueConstraintViolationException|InvalidTypeException|ResourceNotFoundException|InsufficientFundsException $e) {
            $this->entityManager->rollback();

            $this->logger->error($e->getMessage());
            $this->eventService->raiseTransferError($transactionFrom, $transactionTo, $e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->emergency($e->getMessage());
            $this->eventService->raiseTransferError($transactionFrom, $transactionTo, $e->getMessage());

            return;
        }

        $this->entityManager->commit();
    }
}