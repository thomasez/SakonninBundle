<?php

namespace BisonLab\SakonninBundle\Repository;

use BisonLab\ContextBundle\Repository\ContextBaseRepository;
use BisonLab\SakonninBundle\Entity\SakonninFileContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SakonninFileContextRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SakonninFileContextRepository extends ContextBaseRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, SakonninFileContext::class);
    }

    /*
     * This should explain itself, but the context here is of course not a
     * FileContext entity, but the keys to find one. And the answer to the
     * question is easy, if there are one or more FileContexts, there are
     * files.
     */
    public function contextHasFiles($context_data, $with_contexts = false)
    {
        $qb = $this->createQueryBuilder('fc')
              ->where('fc.system = :system')
              ->andWhere('fc.object_name = :object_name')
              ->andWhere('fc.external_id = :external_id')
              ->setParameter("system", $context_data['system'])
              ->setParameter("object_name", $context_data['object_name'])
              ->setParameter("external_id", (string)$context_data['external_id'])
              ->setMaxResults(1);

        if ($with_contexts) {
            return $qb->getQuery()->getResult();
        } else {
            $qb->setMaxResults(1);
            $file_contexts = $qb->getQuery()->getResult();
            return !empty($file_contexts);
        }
    }
}
