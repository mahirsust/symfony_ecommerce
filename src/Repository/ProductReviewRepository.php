<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductReview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductReview>
 */
class ProductReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductReview::class);
    }

    /**
     * Find approved reviews for a product
     *
     * @param Product $product
     * @return array
     */
    public function findApprovedByProduct(Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :product')
            ->andWhere('r.isApproved = :isApproved')
            ->setParameter('product', $product)
            ->setParameter('isApproved', true)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews by user
     *
     * @param User $user
     * @return array
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending reviews (not approved)
     *
     * @return array
     */
    public function findPendingReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isApproved = :isApproved')
            ->setParameter('isApproved', false)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate average rating for a product
     *
     * @param Product $product
     * @return float|null
     */
    public function calculateAverageRating(Product $product): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->andWhere('r.product = :product')
            ->andWhere('r.isApproved = :isApproved')
            ->setParameter('product', $product)
            ->setParameter('isApproved', true)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? round((float)$result, 1) : null;
    }

    /**
     * Count total reviews for a product
     *
     * @param Product $product
     * @return int
     */
    public function countApprovedReviews(Product $product): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.product = :product')
            ->andWhere('r.isApproved = :isApproved')
            ->setParameter('product', $product)
            ->setParameter('isApproved', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if user has already reviewed a product
     *
     * @param Product $product
     * @param User $user
     * @return bool
     */
    public function hasUserReviewedProduct(Product $product, User $user): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.product = :product')
            ->andWhere('r.user = :user')
            ->setParameter('product', $product)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $count > 0;
    }
}
