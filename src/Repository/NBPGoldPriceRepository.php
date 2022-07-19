<?php

namespace App\Repository;

use App\Entity\NBPGoldPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NBPGoldPrice>
 *
 * @method NBPGoldPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method NBPGoldPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method NBPGoldPrice[]    findAll()
 * @method NBPGoldPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NBPGoldPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NBPGoldPrice::class);
    }

    public function add(NBPGoldPrice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NBPGoldPrice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return NBPGoldPrice[] Returns an array of NBPGoldPrice objects
     */
    public function findDateInRange(string $from, string $to): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.date >= :from')
            ->andWhere('n.date <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?NBPGoldPrice
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
