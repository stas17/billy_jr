<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestHoldCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('billy:test-hold')
            ->setDescription('Just test hold')
            ->addArgument('customer_login', InputArgument::REQUIRED)
            ->addArgument('amount', InputArgument::REQUIRED)
            ->addArgument('order_id', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('old_sound_rabbit_mq.hold_producer')->publish(json_encode($input->getArguments()));
    }
}