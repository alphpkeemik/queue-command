<?php declare(strict_types=1);

/*
 * This file is part of the Ambientia QueueCommand package.
 */

namespace Ambientia\QueueCommand\Tests;

use Ambientia\QueueCommand\AbstractMinimumCriteriaBuilder;
use Ambientia\QueueCommand\QueueCriteria;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\ORM\Query\QueryExpressionVisitor;
use PHPUnit\Framework\TestCase;

/**
 * @author mati.andreas@ambientia.ee
 */
class AbstractMinimumCriteriaBuilderTest extends TestCase
{

    public function testAcceptance(): void
    {
        $provider = new class extends AbstractMinimumCriteriaBuilder {
            public function build(): QueueCriteria
            {
                return $this->buildMinimum();
            }

        };
        $time = time();
        $criteria = $provider->build();

        $visitor = $this->createMock(ExpressionVisitor::class);
        $visitor
            ->expects($this->any())
            ->method($this->callback(function (string $name) {

                return false;
            }));
        $alias = uniqid();
        $visitor = new QueryExpressionVisitor([$alias]);

        /** @var \Doctrine\ORM\Query\Expr\Andx $expr */
        $expr = $criteria->getWhereExpression()->visit($visitor);
        $params = $visitor->getParameters();

        $this->assertSame(
            "$alias.status IS NULL AND ($alias.ttl IS NULL OR $alias.ttl <= :ttl)",
            (string)$expr
        );
        $this->assertCount(1, $params);

        $this->assertSame($time, $params[0]->getValue()->getTimestamp());

        $this->assertSame([], $criteria->getOrderings());
    }


}