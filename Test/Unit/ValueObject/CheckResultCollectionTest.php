<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ValueObject;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;
use PHPUnit\Framework\TestCase;

class CheckResultCollectionTest extends TestCase
{
    public function testIsEmptyByDefault(): void
    {
        $collection = new CheckResultCollection();
        $this->assertSame([], $collection->get());
    }

    public function testAddKeepsInsertionOrder(): void
    {
        $first = new CheckResult('first', 1, true, '');
        $second = new CheckResult('second', 2, false, '');

        $collection = new CheckResultCollection();
        $collection->add($first);
        $collection->add($second);

        $this->assertSame([$first, $second], $collection->get());
    }

    public function testConstructorSeedsResults(): void
    {
        $first = new CheckResult('first', 1, true, '');
        $second = new CheckResult('second', 2, true, '');

        $collection = new CheckResultCollection([$first]);
        $collection->add($second);

        $this->assertSame([$first, $second], $collection->get());
    }
}
