<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.
// Returns the private 'old_sound_rabbit_mq.command.rpc_server_command' shared service.

include_once $this->targetDirs[3].'/vendor/symfony/console/Command/Command.php';
include_once $this->targetDirs[3].'/vendor/symfony/dependency-injection/ContainerAwareInterface.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/Command/BaseRabbitMqCommand.php';
include_once $this->targetDirs[3].'/vendor/php-amqplib/rabbitmq-bundle/Command/RpcServerCommand.php';

$this->privates['old_sound_rabbit_mq.command.rpc_server_command'] = $instance = new \OldSound\RabbitMqBundle\Command\RpcServerCommand();

$instance->setName('rabbitmq:rpc-server');

return $instance;