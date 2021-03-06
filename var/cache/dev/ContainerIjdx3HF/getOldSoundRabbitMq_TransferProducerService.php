<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.
// Returns the public 'old_sound_rabbit_mq.transfer_producer' shared service.

include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/BaseAmqp.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/ProducerInterface.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/RabbitMq/Producer.php';

$this->services['old_sound_rabbit_mq.transfer_producer'] = $instance = new \OldSound\RabbitMqBundle\RabbitMq\Producer(($this->services['old_sound_rabbit_mq.connection.default'] ?? $this->load('getOldSoundRabbitMq_Connection_DefaultService.php')));

$instance->setExchangeOptions(array('name' => 'billy.v1.transfer', 'type' => 'direct', 'passive' => false, 'durable' => true, 'auto_delete' => false, 'internal' => false, 'nowait' => false, 'declare' => true, 'arguments' => NULL, 'ticket' => NULL));
$instance->setQueueOptions(array('name' => '', 'declare' => false));
if ($this->has('event_dispatcher')) {
    $instance->setEventDispatcher(($this->services['event_dispatcher'] ?? $this->getEventDispatcherService()));
}

return $instance;
