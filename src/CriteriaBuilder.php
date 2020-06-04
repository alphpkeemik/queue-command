<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand;

use DateTime;
use Doctrine\Common\Collections\Criteria;

/**
 * @author mati.andreas@ambientia.ee
 */
class CriteriaBuilder implements CriteriaBuilderInterface
{
    public function build(): QueueCriteria
    {
        $expr = Criteria::expr();
        $criteria = new QueueCriteria();

        $criteria->andWhere($expr->isNull('status'));
        $criteria->andWhere(
            $expr->orX(
                $expr->isNull('ttl'),
                $expr->lte('ttl', new DateTime())
            )
        );
        $criteria->orderBy([
            'priority' => Criteria::DESC,
            'id' => Criteria::ASC,
        ]);

        return $criteria;
    }

}