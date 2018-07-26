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

class PaymentConsumer implements ConsumerInterface
{
//php bin/console billy:test-payment billy-jr 54 2323
//php bin/console rabbitmq:consumer payment

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

        //Создаем (не сохраняем) транзакцию. Внутри проверка на валидность данных
        try {
            $transaction = $this->transactionService->createTransaction($message->getBody(), 'payment');
        } catch (InvalidParameterException $e) {
            $this->logger->error($e->getMessage(), ['message' => $message->getBody()]);

            return;
        } catch (\Throwable $e) {
            $this->logger->emergency($e->getMessage(), ['message' => $message->getBody()]);

            return;
        }

        $this->entityManager->beginTransaction();
        try {
            //Тут основная работа))). Получаем баланс, сохраняем транзакцию, изменяем баланс.
            //Все это делаем атамарно. Какие могут вазникнуть проблемы:
            //  1. Незнакомый пользователь
            //  2. Транзакция с таким order_id, type уже существует
            //Так же запихиваем получение баланса в транзакцию.
            //Потому что важно, что бы никто не изменил его паралельно.
            //Да и рейз эвента запихал в транзакцию, что бы не париться с тем как быть если он обламался.
            $balance = $this->balanceService->getUserBalance($transaction->getCustomerLogin());
            $this->transactionService->saveTransaction($transaction);
            $this->balanceService->updateBalance($balance, $transaction);

            $this->eventService->raiseSuccess($transaction);
        } catch (UniqueConstraintViolationException|InvalidTypeException|ResourceNotFoundException $e) {
            $this->entityManager->rollback();

            $this->logger->error($e->getMessage(), ['message' => $message->getBody()]);
            $this->eventService->raiseError($transaction, $e->getMessage());
            var_dump($e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->emergency($e->getMessage(), ['message' => $message->getBody()]);
            $this->eventService->raiseError($transaction, $e->getMessage());
            var_dump($e->getMessage());

            return;
        }

        $this->entityManager->commit();
    }
}