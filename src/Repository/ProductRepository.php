<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Search for products by term
     *
     * @param string $searchTerm
     * @return array
     */
    public function searchByTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :searchTerm')
            ->orWhere('p.description LIKE :searchTerm')
            ->orWhere('p.sku LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find related products (same category, excluding current product)
     *
     * @param Product $product
     * @param int $limit
     * @return array
     */
    public function findRelatedProducts(Product $product, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->setParameter('category', $product->getCategory())
            ->andWhere('p.id != :productId')
            ->setParameter('productId', $product->getId())
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->setMaxResults($limit)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search products by query string
     *
     * @param string $query
     * @return array
     */
    public function searchProducts(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :query')
            ->orWhere('p.description LIKE :query')
            ->orWhere('p.sku LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products by category slug
     *
     * @param string $categorySlug
     * @return array
     */
    public function findByCategory(string $categorySlug): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->where('c.slug = :categorySlug')
            ->setParameter('categorySlug', $categorySlug)
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products with low stock
     *
     * @param int $threshold
     * @return array
     */
    public function findLowStockProducts(int $threshold = 5): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock <= :threshold')
            ->setParameter('threshold', $threshold)
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
