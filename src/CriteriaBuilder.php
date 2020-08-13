<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use Doctrine\Common\Collections\Criteria;

/**
 * @author mati.andreas@ambientia.ee
 */
class CriteriaBuilder extends AbstractMinimumCriteriaBuilder
{
    public function build(): QueueCriteria
    {
        $criteria = $this->buildMinimum();
        $criteria->orderBy([
            'priority' => Criteria::DESC,
            'id' => Criteria::ASC,
        ]);

        return $criteria;
    }

}