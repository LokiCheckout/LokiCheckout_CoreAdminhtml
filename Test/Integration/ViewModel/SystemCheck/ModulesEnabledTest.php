<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\ModulesEnabled;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

#[AppArea('adminhtml')]
class ModulesEnabledTest extends TestCase
{
    public function testModuleDependenciesAreReported(): void
    {
        $modulesEnabled = ObjectManager::getInstance()->create(ModulesEnabled::class);
        $checkResults = $modulesEnabled->getCheckResultCollection()->get();
        $this->assertNotEmpty($checkResults);

        $checkResultsByLabel = [];
        foreach ($checkResults as $checkResult) {
            $checkResultsByLabel[$checkResult->getLabel()] = $checkResult;
        }

        $this->assertArrayHasKey('LokiCheckout_Core', $checkResultsByLabel);
        $coreCheckResult = $checkResultsByLabel['LokiCheckout_Core'];
        $this->assertSame('Enabled', $coreCheckResult->getValue());
        $this->assertTrue($coreCheckResult->isValid());
        $this->assertStringContainsString('LokiCheckout_CoreAdminhtml', $coreCheckResult->getRecommendation());
    }

    public function testCheckResultsAreNotDuplicatedWhenCalledTwice(): void
    {
        $modulesEnabled = ObjectManager::getInstance()->create(ModulesEnabled::class);
        $count = count($modulesEnabled->getCheckResultCollection()->get());

        $this->assertCount($count, $modulesEnabled->getCheckResultCollection()->get());
    }
}
