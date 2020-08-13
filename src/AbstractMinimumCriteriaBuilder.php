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
abstract class AbstractMinimumCriteriaBuilder implements CriteriaBuilderInterface
{
    protected function buildMinimum(): QueueCriteria
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
        return $criteria;
    }

}