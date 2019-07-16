<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 *
 * (c) Ambientia Estonia OÜ
 */

namespace Ambientia\QueueCommand;

use Doctrine\Common\Collections\Criteria;
use LogicException;

/**
 * @author mati.andreas@ambientia.ee
 */
class QueueCriteria extends Criteria
{

    final public function setFirstResult($firstResult)
    {
        if ($firstResult !== null) {
            throw new LogicException('Setting first result not allowed');
        }
        return parent::setFirstResult($firstResult);
    }

    final public function setMaxResults($maxResults)
    {
        if ($maxResults !== null) {
            throw new LogicException('Setting max results not allowed');
        }
        return parent::setMaxResults($maxResults);
    }
}