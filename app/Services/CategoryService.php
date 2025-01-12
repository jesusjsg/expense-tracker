<?php

declare(strict_types = 1);

namespace App\Services;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CategoryService
{
    public function __construct(private readonly EntityManager $entityManager)
    {
    }


    public function create(string $name, User $user): Category
    {
        $category = new Category();

        $category->setUser($user);
        
        return $this->update($category, $name);
    }

    public function getPaginatedCategories(int $start, int $length): array
    {
        $query = $this->entityManager
                    ->getRepository(Category::class)
                    ->createQueryBuilder('c')
                    ->setFirstResult($start)
                    ->setMaxResults($length);
        
        return $query->getQuery()->getResult();
    }

    public function delete(int $id): void
    {
        $categoryId = $this->entityManager->find(Category::class, $id);
        $this->entityManager->remove($categoryId);
        $this->entityManager->flush();
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function update(Category $category, string $name): Category
    {
        $category->setName($name);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}
