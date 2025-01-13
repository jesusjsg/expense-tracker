<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\ValidatorFactoryInterface;
use App\Entity\Category;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\RequestService;
use App\Validators\CreateCategoryValidator;
use App\Validators\UpdateCategoryValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class CategoriesController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly ValidatorFactoryInterface $validatorFactory,
        private readonly CategoryService $categoryService,
        private readonly ResponseFormatter $responseJsonFormatter,
        private readonly RequestService $requestService
    ) {
    }

    public function index(Request $request, Response $response): Response
    {        
        return $this->twig->render($response, 'categories/index.twig');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(CreateCategoryValidator::class)->validate(
            $request->getParsedBody()
        );

        $this->categoryService->create($data['name'], $request->getAttribute('user'));

        return $response->withHeader('Location', '/categories')->withStatus(302);

    }

    public function delete(Request $request ,Response $response, array $args): Response
    {
        $this->categoryService->delete((int) $args['id']);
        return $response;
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $category = $this->categoryService->getById((int) $args['id']);

        if (! $category) {
            return $response->withStatus(404);
        }

        $data = ['id' => $category->getCategoryId(), 'name' =>$category->getName()];
    
        return $this->responseJsonFormatter->json($response, $data);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $this->validatorFactory->make(UpdateCategoryValidator::class)->validate(
            $args + $request->getParsedBody()
        );

        $category = $this->categoryService->getById((int) $data['id']);

        if (! $category) {
            return $response->withStatus(404);
        }

        $this->categoryService->update($category, $data['name']); // if the data contain more than two keys use dto instead array

        return $response;
    }
    
    public function load(Request $request, Response $response): Response
    {
        $queryParams = $this->requestService->getDataTableParams($request);
        $categories = $this->categoryService->getPaginatedCategories($queryParams);

        $setData = function (Category $category) {
            return [
                'id'        => $category->getCategoryId(),
                'name'      => $category->getName(),
                'createdAt' => $category->getCreatedAt()->format('m/d/Y g:i A'),
                'updatedAt' => $category->getCreatedAt()->format('m/d/Y g:i A')
            ];
        };

        $totalCategories = count($categories);

        return $this->responseJsonFormatter->datatable(
            $response,
            array_map($setData, (array) $categories->getIterator()),
            $queryParams->draw,
            $totalCategories
        );
    }
}
