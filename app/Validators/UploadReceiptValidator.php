<?php

declare(strict_types = 1);

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class UploadReceiptValidator implements ValidatorInterface
{
    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['receipt'] ?? null;
        
        if (! $uploadedFile) {
            throw new ValidationException(['receipt' => ['Please select a receipt file']]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['receipt' => ['Failed to upload the receipt file']]);
        }

        $maxFileSize = 5 * 1024 * 1024;

        if ($uploadedFile->getSize() > $maxFileSize) {
            throw new ValidationException(['receipt' => ['Maximun allowed size is 5 MB']]); 
        } 
        
        $filename = $uploadedFile->getClientFilename(); 
        
        if (! preg_match('/^[a-zA-Z0-9\s._-]+$/', $filename)) {
            throw new ValidationException(['receipt' => ['Invalid filename']]);
        }
        
        $allowedMimeType = ['image/jpeg', 'image/png', 'application/pdf'];
        $tmpFilePath = $uploadedFile->getStream()->getMetadata('uri');

        if (! in_array($uploadedFile->getClientMediaType(), $allowedMimeType)) {
            throw new ValidationException(['receipt' => ['Receipt has to be either an image or a pdf document']]);
        }

        $mimeDetector = new FinfoMimeTypeDetector();
        $mimeType = $mimeDetector->detectMimeTypeFromFile($tmpFilePath);

        if (! in_array($mimeType, $allowedMimeType)) {
            throw new ValidationException(['receipt' => ['Invalid file type']]);
        }

        return $data;
    }
}
