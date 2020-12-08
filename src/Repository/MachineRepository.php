<?php

namespace App\Repository;

use App\Entity\Machine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Machine|null find($id, $lockMode = null, $lockVersion = null)
 * @method Machine|null findOneBy(array $criteria, array $orderBy = null)
 * @method Machine[]    findAll()
 * @method Machine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MachineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Machine::class);
    }

    /**
     * @param $machineName
     * @return Machine|null Returns an array of Machine objects
     * @throws NonUniqueResultException
     * used to find a machine object by name in the database
     */
    public function findOneByMachineName($machineName): ?Machine
    {
        return $this->createQueryBuilder('machine')
            ->andWhere('machine.name = :val')
            ->setParameter('val', $machineName)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @param $userId
     * @return Machine|array|int|string|null
     * used to find every machine of particular user in the database
     */
    public function getAllByUserId($userId)
    {
        return $this->createQueryBuilder('machine')
            ->andWhere('machine.user_id = :val')
            ->setParameter('val', $userId)
            ->getQuery()
            ->getArrayResult();
    }
}
