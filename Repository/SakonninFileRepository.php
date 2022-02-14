<?php

namespace BisonLab\SakonninBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\SakonninFile;

/**
 * SakonninFileRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SakonninFileRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, SakonninFile::class);
    }

    public function getOneByContext($system, $object_name, $external_id)
    {
        // This is so annoyng! I Just did not get subselects working, at all.
        $qb2 = $this->_em->createQueryBuilder();

        $qb2->select('sfc')
              ->from('BisonLab\SakonninBundle\Entity\SakonninFileContext', 'sfc')
              ->where('sfc.system = :system')
              ->andWhere('sfc.object_name = :object_name')
              ->andWhere('sfc.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", $external_id)
              ->setMaxResults(1);

        $file_context = $qb2->getQuery()->getResult();

        if (empty($file_context)) { return null; }

        return current($file_context)->getSakonninFile();
    }
    
    public function findByContext($system, $object_name, $external_id)
    {
        // This is so annoyng! I Just did not get subselects working, at all.
        $qb2 = $this->_em->createQueryBuilder();

        $qb2->select('sfc')
              ->from('BisonLab\SakonninBundle\Entity\SakonninFileContext', 'sfc')
              ->where('sfc.system = :system')
              ->andWhere('sfc.object_name = :object_name')
              ->andWhere('sfc.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", $external_id);

        $files = new ArrayCollection();
        foreach($qb2->getQuery()->getResult() as $sfc) {
            $files->add($sfc->getSakonninFile());
        }
        $iterator = $files->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getCreatedAt() > $b->getCreatedAt()) ? -1 : 1;
            });
        return new ArrayCollection(iterator_to_array($iterator));
    }
}
