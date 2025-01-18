<?php

declare(strict_types = 1);

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class TransactionImportValidator implements ValidatorInterface
{
    public function validate(array $data): array
    {
    /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['importFile'] ?? null;

        if (! $uploadedFile) {
            throw new ValidationException(['importFile' => ['Please select a file to import']]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['importFile' => ['Failed to upload the file for import']]);
        }

        $maxFileSize = 20 * 1024 * 1024;

        if ($uploadedFile->getSize() > $maxFileSize) {
            throw new ValidationException(['importFile' => ['Maximum allowed size is 20 MB']]);
        }

        $allowedMimeType = ['text/csv'];

        if (! in_array($uploadedFile->getClientMediaType(), $allowedMimeType)) {
            throw new ValidationException(['importFile' => ['Please select a CSV file to import']]);
        }

        $detector = new FinfoMimeTypeDetector();

        $mimeType = $detector->detectMimeTypeFromFile($uploadedFile->getStream()->getMetadata('uri'));

        if (! in_array($mimeType, $allowedMimeType)) {
            throw new ValidationException(['importFile' => ['Invalid file type']]);
        }

        return $data;
    }
}
