<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\ValidatorFactoryInterface;
use App\DataObjects\TransactionData;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Validators\TransactionImportValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;

class TransactionImportController
{
    public function __construct(
        private readonly ValidatorFactoryInterface $validatorFactoryInterface,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
    ) {   
    }

    public function import(Request $request, Response $response): Response
    {
    /** @var UploadedFileInterface $file */
        $file = $this->validatorFactoryInterface->make(TransactionImportValidator::class)->validate(
            $request->getUploadedFiles()
        )['importFile'];

        $user = $request->getAttribute('user');
        $resource = fopen($file->getStream()->getMetadata('uri'), 'r');

        fgetcsv($resource);

        while (($row = fgetcsv($resource)) !== false) {
            [$date, $description, $category, $amount] = $row;

            $date = new \DateTime($date);
            $category = $this->categoryService->findByName($category);
            $amount = str_replace(['$', ','], '', $amount);

            $transactionData = new TransactionData($description, (float) $amount, $date, $category);

            $this->transactionService->create($transactionData, $user);
        }

        return $response;
    }
}
