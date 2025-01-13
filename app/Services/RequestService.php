<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\SessionInterface;
use App\DataObjects\DataTableParams;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequestService
{
    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function getReferer(Request $request): string
    {
        $referer = $request->getHeader('referer')[0] ?? '';

        if (! $referer) {
            return $this->session->get('previousUrl');
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);

        if ($refererHost !== $request->getUri()->getHost()) {
            $referer = $this->session->get('previousUrl');
        }

        return $referer;
    }
    
    public function isXhr(Request $request): bool
    {
        return $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
    
    public function getDatatableParams(Request $request): DataTableParams
    {
        $queryParams = $request->getQueryParams();

        $orderBy = $queryParams['columns'][$queryParams['order'][0]['column']]['data'];
        $orderDir = $queryParams['order'][0]['dir'];

        return new DataTableParams(
            (int) $queryParams['start'],
            (int) $queryParams['length'],
            $orderBy,
            $orderDir,
            $queryParams['search']['value'],
            (int) $queryParams['draw'],
        );
    }
}   

