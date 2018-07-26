<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BalanceRepository")
 */
class Balance
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $customer_login;

    /**
     * @ORM\Column(type="bigint")
     */
    private $balance;

    public function getId()
    {
        return $this->id;
    }

    public function getCustomerLogin(): ?string
    {
        return $this->customer_login;
    }

    public function setCustomerLogin(string $customer_login): self
    {
        $this->customer_login = $customer_login;

        return $this;
    }

    public function getBalance(): ?int
    {
        return $this->balance;
    }

    public function setBalance(int $balance): self
    {
        $this->balance = $balance;

        return $this;
    }
}
