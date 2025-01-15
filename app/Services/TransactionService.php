<?php

declare(strict_types = 1);

namespace App\Services;

use App\DataObjects\DataTableParams;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{
    public function __construct(private readonly EntityManager $entityManager)
    {
    }

    public function create(TransactionData $transactionData, User $user): Transaction
    {
        $transaction = new Transaction();

        $transaction->setUser($user);

        return $this->update($transaction, $transactionData);
    }

    public function delete(int $id): void
    {
        $transaction = $this->entityManager->find(Transaction::class, $id);

        $this->entityManager->remove($transaction);
        $this->entityManager->flush();
    }

    public function getPaginatedTransactions(DataTableParams $dataTableParams): Paginator
    {
        $queryParams = $this->entityManager
            ->getRepository(Transaction::class)                
            ->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->setFirstResult($dataTableParams->start)
            ->setMaxResults($dataTableParams->length);

        $orderBy = in_array($dataTableParams->orderBy, ['description', 'amount', 'date', 'category']) ? $dataTableParams->orderBy : 'date';
        
        $orderDir = strtolower($dataTableParams->orderDir) === 'asc' ? 'asc' : 'desc';

        if (! empty($dataTableParams->searchValue)) {
            $queryParams->where('t.description LIKE :description')
                ->setParameter('description', '%' . addcslashes($dataTableParams->searchValue, '%_') . '%');
        }
        
        if ($orderBy === 'category') {
            $queryParams->orderBy('c.name', $orderDir);
        } else {
            $queryParams->orderBy('t.' . $orderBy, $orderDir);
        }

        return new Paginator($queryParams);

    }

    public function update(Transaction $transaction, TransactionData $transactionData): Transaction
    {
        $transaction->setDescription($transactionData->description);
        $transaction->setAmount($transactionData->amount);
        $transaction->setDate($transactionData->date);
        $transaction->setCategory($transactionData->category);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    public function getById(int $id): ?Transaction
    {
        return $this->entityManager->find(Transaction::class, $id);
    }
}
