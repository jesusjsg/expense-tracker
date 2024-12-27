<?php

declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table('receipts')]
class Receipt
{
    #[Id, Column(options: ['unsigned' => true]), GeneratedValue()]
    private int $id;

    #[Column]
    private string $fileName;

    #[Column(name: 'created_at')]
    private \DateTime $createddAt;

    #[ManyToOne(inversedBy: 'receipts')]
    private Transaction $transaction;

    public function getReceiptId(): int
    {
        return $this->id;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getCreateddAt(): \DateTime
    {
        return $this->createddAt;
    }

    public function setCreateddAt(\DateTime $createddAt): self
    {
        $this->createddAt = $createddAt;

        return $this;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): self
    {
        $transaction->addReceipt($this);
        $this->transaction = $transaction;

        return $this;
    }
}