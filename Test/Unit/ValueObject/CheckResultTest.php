<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ValueObject;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use PHPUnit\Framework\TestCase;

class CheckResultTest extends TestCase
{
    public function testGetters(): void
    {
        $checkResult = new CheckResult('Label', 'Value', true, 'Recommendation', 'https://example.com/');

        $this->assertSame('Label', $checkResult->getLabel());
        $this->assertSame('Value', $checkResult->getValue());
        $this->assertTrue($checkResult->isValid());
        $this->assertSame('Recommendation', $checkResult->getRecommendation());
        $this->assertSame('https://example.com/', $checkResult->getUrl());
    }

    public function testUrlDefaultsToNull(): void
    {
        $checkResult = new CheckResult('Label', null, false, 'Recommendation');

        $this->assertNull($checkResult->getValue());
        $this->assertFalse($checkResult->isValid());
        $this->assertNull($checkResult->getUrl());
    }
}
