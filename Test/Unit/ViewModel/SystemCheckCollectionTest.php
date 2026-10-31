<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ViewModel;

use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheckCollection;
use PHPUnit\Framework\TestCase;

class SystemCheckCollectionTest extends TestCase
{
    public function testIsEmptyByDefault(): void
    {
        $this->assertSame([], (new SystemCheckCollection())->getSystemChecks());
    }

    public function testReturnsInjectedSystemChecks(): void
    {
        $systemChecks = [
            'foo' => $this->createStub(SystemCheckInterface::class),
            'bar' => $this->createStub(SystemCheckInterface::class),
        ];

        $this->assertSame($systemChecks, (new SystemCheckCollection($systemChecks))->getSystemChecks());
    }
}
