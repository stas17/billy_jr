<?php

namespace App\Services;

use App\Entity\Transaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventService
{
    /** @var ContainerInterface */
    private $container;

    /**
     * EventService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Transaction $transaction
     * @param string $errorMessage
     */
    public function raiseError(Transaction $transaction, string $errorMessage)
    {
        $this->container
            ->get('old_sound_rabbit_mq.billi_event_producer')
            ->publish(\json_encode(
                [
                    'status' => 'error',
                    'order_id' => $transaction->getOrderId(),
                    'type' => $transaction->getType(),
                    'customer_login' => $transaction->getCustomerLogin(),
                    'error_message' => $errorMessage
                ]
            ));

    }

    /**
     * @param Transaction $transaction
     */
    public function raiseSuccess(Transaction $transaction)
    {
        $this->container
            ->get('old_sound_rabbit_mq.billi_event_producer')
            ->publish(\json_encode(
                [
                    'status' => 'success',
                    'order_id' => $transaction->getOrderId(),
                    'customer_login' => $transaction->getCustomerLogin(),
                    'type' => $transaction->getType()
                ]
            ));

    }

    /**
     * @param Transaction $transactionFrom
     * @param Transaction $transactionTo
     * @param string $errorMessage
     */
    public function raiseTransferError(Transaction $transactionFrom, Transaction $transactionTo, string $errorMessage)
    {
        $this->container
            ->get('old_sound_rabbit_mq.billi_event_producer')
            ->publish(\json_encode(
                [
                    'status' => 'error',
                    'order_id' => $transactionTo->getOrderId(),
                    'type' => 'transfer',
                    'customer_login_to' => $transactionTo->getCustomerLogin(),
                    'customer_login_from' => $transactionFrom->getCustomerLogin(),
                    'error_message' => $errorMessage
                ]
            ));

    }

    /**
     * @param Transaction $transactionFrom
     * @param Transaction $transactionTo
     */
    public function raiseTransferSuccess(Transaction $transactionFrom, Transaction $transactionTo)
    {
        $this->container
            ->get('old_sound_rabbit_mq.billi_event_producer')
            ->publish(\json_encode(
                [
                    'status' => 'success',
                    'order_id' => $transactionTo->getOrderId(),
                    'type' => 'transfer',
                    'customer_login_to' => $transactionTo->getCustomerLogin(),
                    'customer_login_from' => $transactionFrom->getCustomerLogin()
                ]
            ));

    }
}
