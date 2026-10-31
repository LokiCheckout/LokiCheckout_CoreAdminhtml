<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ViewModel;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheckCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SystemCheckCollectionTest extends TestCase
{
    public function testIsEmptyByDefault(): void
    {
        $systemCheckCollection = new SystemCheckCollection($this->createStub(LoggerInterface::class));

        $this->assertSame([], $systemCheckCollection->getSystemChecks());
        $this->assertSame([], $systemCheckCollection->getCheckResults());
    }

    public function testReturnsInjectedSystemChecks(): void
    {
        $systemChecks = [
            'foo' => $this->createStub(SystemCheckInterface::class),
            'bar' => $this->createStub(SystemCheckInterface::class),
        ];

        $systemCheckCollection = new SystemCheckCollection($this->createStub(LoggerInterface::class), $systemChecks);
        $this->assertSame($systemChecks, $systemCheckCollection->getSystemChecks());
    }

    public function testCheckResultsOfAllSystemChecksAreCombined(): void
    {
        $first = new CheckResult('first', 1, true, '');
        $second = new CheckResult('second', 2, true, '');
        $third = new CheckResult('third', 3, false, '');

        $systemCheckCollection = new SystemCheckCollection($this->createStub(LoggerInterface::class), [
            'foo' => $this->createSystemCheck([$first, $second]),
            'bar' => $this->createSystemCheck([$third]),
        ]);

        $this->assertSame([$first, $second, $third], $systemCheckCollection->getCheckResults());
    }

    public function testFailingSystemCheckBecomesErrorRow(): void
    {
        $exception = new RuntimeException('Database gone');
        $failingSystemCheck = $this->createStub(SystemCheckInterface::class);
        $failingSystemCheck->method('getCheckResultCollection')->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('failing'), ['exception' => $exception]);

        $valid = new CheckResult('valid', 1, true, '');
        $systemCheckCollection = new SystemCheckCollection($logger, [
            'failing' => $failingSystemCheck,
            'valid' => $this->createSystemCheck([$valid]),
        ]);

        $checkResults = $systemCheckCollection->getCheckResults();
        $this->assertCount(2, $checkResults);
        $this->assertSame('failing', $checkResults[0]->getLabel());
        $this->assertFalse($checkResults[0]->isValid());
        $this->assertFalse($checkResults[0]->isWarning());
        $this->assertStringContainsString('Database gone', $checkResults[0]->getRecommendation());
        $this->assertSame($valid, $checkResults[1]);
    }

    public function testInvalidSystemCheckBecomesErrorRow(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $systemCheckCollection = new SystemCheckCollection($logger, ['invalid' => new \stdClass()]);

        $checkResults = $systemCheckCollection->getCheckResults();
        $this->assertCount(1, $checkResults);
        $this->assertSame('invalid', $checkResults[0]->getLabel());
        $this->assertFalse($checkResults[0]->isValid());
    }

    /**
     * @param CheckResult[] $checkResults
     */
    private function createSystemCheck(array $checkResults): SystemCheckInterface
    {
        $systemCheck = $this->createStub(SystemCheckInterface::class);
        $systemCheck->method('getCheckResultCollection')->willReturn(new CheckResultCollection($checkResults));

        return $systemCheck;
    }
}
