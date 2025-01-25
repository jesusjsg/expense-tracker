<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\TransactionData;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\RequestService;
use App\Services\TransactionService;
use App\Validators\TransactionValidator;
use App\Validators\ValidatorFactory;
use DateTime;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class TransactionsController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly ValidatorFactory $validatorFactory,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
        private readonly RequestService $requestService,
        private readonly ResponseFormatter $responseFormatter,
        private readonly EntityManagerServiceInterface $entityManagerService
    ) {  
    }

    public function index(Response $response): Response
    {
        return $this->twig->render(
            $response,
            'transactions/index.twig',
            ['categories' => $this->categoryService->getCategoryNames()]
        );
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(TransactionValidator::class)->validate(
            $request->getParsedBody()
        );
        
        $transaction = $this->transactionService->create(
            new TransactionData(
                $data['description'],
                (float) $data['amount'],
                new DateTime($data['date']),
                $data['category']
            ),

            $request->getAttribute('user')
        );

        $this->entityManagerService->sync($transaction);

        return $response;
    }

    public function get(Response $response, Transaction $transaction): Response
    {
        $data = [
            'id'          => $transaction->getTransactionId(),
            'description' => $transaction->getDescription(),
            'amount'      => $transaction->getAmount(),
            'date'        => $transaction->getDate()->format('Y-m-d\TH:i'),
            'category'    => $transaction->getCategory()->getCategoryId(),
        ];

        return $this->responseFormatter->json($response, $data);
    }

    public function update(Request $request, Response $response, Transaction $transaction): Response
    {
        $data = $this->validatorFactory->make(TransactionValidator::class)->validate(
            $request->getParsedBody()
        );

        $this->entityManagerService->sync(
            $this->transactionService->update(
                $transaction,
                new TransactionData(
                    $data['description'],
                    (float) $data['amount'],
                    new DateTime($data['date']),
                    $data['category']
                )
            )
        );

        return $response;
    } 

    public function delete(Response $response, Transaction $transaction): Response
    {
        $this->entityManagerService->delete($transaction, true);

        return $response;
    }
    
    public function load(Request $request, Response $response): Response
    {
        $params = $this->requestService->getDatatableParams($request);
        $transactions = $this->transactionService->getPaginatedTransactions($params);
        $setData = function (Transaction $transaction) {
            return [
                'id'          => $transaction->getTransactionId(),
                'description' => $transaction->getDescription(),
                'amount'      => $transaction->getAmount(),
                'date'        => $transaction->getDate()->format('m/d/Y g:i A'),
                'wasReviewed' => $transaction->wasReviewed(),
                'category'    => $transaction->getCategory()?->getName(),
                'receipts'    => $transaction->getReceipts()->map(fn(Receipt $receipt) => [
                    'name'    => $receipt->getFilename(),
                    'id'      => $receipt->getReceiptId(),
                ])->toArray(),
            ];
        };

        $totalTransactions = count($transactions);

        return $this->responseFormatter->datatable(
            $response,
            array_map($setData, (array) $transactions->getIterator()),
            $params->draw,
            $totalTransactions
        );
    }

    public function toggleReviewed(Response $response, Transaction $transaction): Response
    {
        $this->transactionService->toggleReviewed($transaction);
        $this->entityManagerService->sync();

        return $response;
    }
}
