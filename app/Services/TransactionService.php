<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\DataTableParams;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager)
    {
    }

    public function create(TransactionData $transactionData, User $user): Transaction
    {
        $transaction = new Transaction();

        $transaction->setUser($user);

        return $this->update($transaction, $transactionData);
    }

    public function getPaginatedTransactions(DataTableParams $dataTableParams): Paginator
    {
        $queryParams = $this->entityManager
            ->getRepository(Transaction::class)                
            ->createQueryBuilder('t')
            ->select('t', 'c', 'r')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.receipts', 'r')
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

        return $transaction;
    }

    public function getById(int $id): ?Transaction
    {
        return $this->entityManager->find(Transaction::class, $id);
    }
    
    public function toggleReviewed(Transaction $transaction): void
    {
        $transaction->setWasReviewed(! $transaction->wasReviewed());
    }
}
