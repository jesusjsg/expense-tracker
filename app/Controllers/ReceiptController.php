<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\Services\ReceiptService;
use App\Services\TransactionService;
use App\Validators\UploadReceiptValidator;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\Stream;

class ReceiptController
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ValidatorFactoryInterface $validatorFactoryInterface,
        private readonly TransactionService $transactionService,
        private readonly ReceiptService $receiptService,
        private readonly EntityManagerServiceInterface $entityManagerService
    ) {
    }

    public function store(Request $request, Response $response, array $args): Response
    {
        /**@var UploadedFileInterface $file */
        $file = $this->validatorFactoryInterface->make(UploadReceiptValidator::class)->validate(
            $request->getUploadedFiles()
        )['receipt'];

        $fileName = $file->getClientFilename();

        $id = (int) $args['id'];

        if (! $id || ! $transaction = $this->transactionService->getById($id)) {
            return $response->withStatus(404);
        }

        $randomFileName = bin2hex(random_bytes(25));

        $this->filesystem->write('receipts/' . $randomFileName, $file->getStream()->getContents());

        $receipt = $this->receiptService->create($transaction, $fileName, $randomFileName, $file->getClientMediaType());
        
        $this->entityManagerService->sync($receipt);

        return $response;
    }

    public function download(Request $request, Response $response, array $args): Response
    {
        $transactionId = (int) $args['transactionId'];
        $receiptId = (int) $args['id'];
        
        if (! $transactionId || ! $this->transactionService->getById($transactionId)) {
            return $response->withStatus(404);
        }

        if (! $receiptId || ! ($receipt = $this->receiptService->getById($receiptId))) {
            return $response->withStatus(404);
        }

        if ($receipt->getTransaction()->getTransactionId() !== $transactionId) {
            return $response->withStatus(401);
        }
        
        $file = $this->filesystem->readStream('receipts/' . $receipt->getStorageFilename());

        $response = $response->withHeader(
            'Content-Disposition',
            'inline; filename="' . $receipt->getFilename() . '"',
        )->withHeader('Content-Type', $receipt->getMediaType());

        return $response->withBody(new Stream($file));
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $transactionId = (int) $args['transactionId'];
        $receiptId = (int) $args['id'];

        if (! $transactionId || ! $this->transactionService->getById($transactionId)) {
            return $response->withStatus(404);
        }

        if (! $receiptId || ! ($receipt = $this->receiptService->getById($receiptId))) {
            return $response->withStatus(404);
        }

        if ($receipt->getTransaction()->getTransactionId() !== $transactionId) {
            return $response->withStatus(401);
        }

        $this->filesystem->delete('receipts/' . $receipt->getStorageFilename());
        
        $this->entityManagerService->delete($receipt, true);

        return $response;
    }
}
