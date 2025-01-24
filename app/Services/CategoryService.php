<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\DataTableParams;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CategoryService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager)
    {
    }

    public function create(string $name, User $user): Category
    {
        $category = new Category();

        $category->setUser($user);
        
        return $this->update($category, $name);
    }

    public function getPaginatedCategories(DataTableParams $datatableParams): Paginator
    {
        $query = $this->entityManager
            ->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->setFirstResult($datatableParams->start)
            ->setMaxResults($datatableParams->length);

        $orderBy = in_array($datatableParams->orderBy, ['name', 'createdAt', 'updatedAt']) ? $datatableParams->orderBy : 'updatedAt';
        $orderDir = strtolower($datatableParams->orderDir) === 'asc' ? 'asc' : 'desc';
        
        if (! empty($datatableParams->searchValue)) {
            $query->where('c.name LIKE :name')->setParameter('name', '%' . addcslashes($datatableParams->searchValue, '%_') . '%');
        }

        $query->orderBy('c.' . $orderBy, $orderDir);
        
        return new Paginator($query);
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function update(Category $category, string $name): Category
    {
        $category->setName($name);

        return $category;
    }

    public function getCategoryNames(): array
    {
        return $this->entityManager
            ->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->select('c.id', 'c.name')
            ->getQuery()
            ->getArrayResult();

    }

    public function findByName(string $name): ?Category
    {
        return $this->entityManager->getRepository(Category::class)->findBy(['name' => $name])[0] ?? null;
    }

    public function getAllKeyByName(): array
    {
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $categoryMap = [];

        foreach ($categories as $category) {
            $categoryMap[strtolower($category->getName())] = $category;
        }
        return $categoryMap;
    }
}
