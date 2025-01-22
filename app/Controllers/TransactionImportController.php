<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\ValidatorFactoryInterface;
use App\Validators\TransactionImportValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use App\Services\TransactionImportService;

class TransactionImportController
{
    public function __construct(
        private readonly ValidatorFactoryInterface $validatorFactoryInterface,
        private readonly TransactionImportService $transactionImportService
    ) {   
    }

    public function import(Request $request, Response $response): Response
    {
    /** @var UploadedFileInterface $file */
        $file = $this->validatorFactoryInterface->make(TransactionImportValidator::class)->validate(
            $request->getUploadedFiles()
        )['importFile'];

        $user = $request->getAttribute('user');
        $this->transactionImportService->importFromFile($file->getStream()->getMetadata('uri'), $user);

        return $response;
    }
}
