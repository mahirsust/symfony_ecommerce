<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Check if a user has purchased a specific product
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function hasUserPurchasedProduct(User $user, Product $product): bool
    {
        $count = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.orderItems', 'oi')
            ->where('o.userRef = :user')
            ->andWhere('oi.product = :product')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->setParameter('statuses', ['completed', 'delivered', 'shipped'])
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Find an order where the user purchased a specific product
     *
     * @param User $user
     * @param Product $product
     * @return Order|null
     */
    public function findOrderWithProduct(User $user, Product $product): ?Order
    {
        return $this->createQueryBuilder('o')
            ->join('o.orderItems', 'oi')
            ->where('o.userRef = :user')
            ->andWhere('oi.product = :product')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->setParameter('statuses', ['completed', 'delivered', 'shipped'])
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
