<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.
// Returns the public 'old_sound_rabbit_mq.transfer_consumer' shared service.

include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/ConsumerInterface.php';
include_once $this->targetDirs[3].'/src/Consumer/TransferConsumer.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/BaseAmqp.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/DequeuerInterface.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/BaseConsumer.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/Consumer.php';
include_once $this->targetDirs[3].'/src/Services/EventService.php';

$this->services['old_sound_rabbit_mq.transfer_consumer'] = $instance = new \OldSound\RabbitMqBundle\RabbitMq\Consumer(($this->services['old_sound_rabbit_mq.connection.default'] ?? $this->load('getOldSoundRabbitMq_Connection_DefaultService.php')));

$instance->setExchangeOptions(array('name' => 'billy.v1.transfer', 'type' => 'direct', 'passive' => false, 'durable' => true, 'auto_delete' => false, 'internal' => false, 'nowait' => false, 'declare' => true, 'arguments' => NULL, 'ticket' => NULL));
$instance->setQueueOptions(array('name' => 'billy.v1.transfer', 'passive' => false, 'durable' => true, 'exclusive' => false, 'auto_delete' => false, 'nowait' => false, 'declare' => true, 'arguments' => NULL, 'ticket' => NULL, 'routing_keys' => array()));
$instance->setCallback(array(0 => new \App\Consumer\TransferConsumer(($this->services['doctrine.orm.default_entity_manager'] ?? $this->load('getDoctrine_Orm_DefaultEntityManagerService.php')), ($this->privates['monolog.logger'] ?? $this->load('getMonolog_LoggerService.php')), ($this->privates['App\Services\EventService'] ?? $this->privates['App\Services\EventService'] = new \App\Services\EventService($this)), ($this->privates['App\Services\TransactionService'] ?? $this->load('getTransactionServiceService.php')), ($this->privates['App\Services\BalanceService'] ?? $this->load('getBalanceServiceService.php'))), 1 => 'execute'));
if ($this->has('event_dispatcher')) {
    $instance->setEventDispatcher(($this->services['event_dispatcher'] ?? $this->getEventDispatcherService()));
}

return $instance;
