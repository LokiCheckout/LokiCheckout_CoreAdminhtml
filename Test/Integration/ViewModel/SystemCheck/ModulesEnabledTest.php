<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\ModulesEnabled;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\ModuleList;
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

    public function testDependencyThatIsNotInstalledIsReportedAsNotInstalled(): void
    {
        $moduleList = $this->createStub(ModuleList::class);
        $moduleList->method('getAll')->willReturn([
            'LokiCheckout_CoreAdminhtml' => [
                'name' => 'LokiCheckout_CoreAdminhtml',
                'sequence' => ['LokiCheckout_Core', 'Vendor_NotInstalledAnywhere'],
            ],
        ]);

        $modulesEnabled = ObjectManager::getInstance()->create(ModulesEnabled::class, ['moduleList' => $moduleList]);

        $checkResultsByLabel = [];
        foreach ($modulesEnabled->getCheckResultCollection()->get() as $checkResult) {
            $checkResultsByLabel[$checkResult->getLabel()] = $checkResult;
        }

        $this->assertSame('Enabled', $checkResultsByLabel['LokiCheckout_Core']->getValue());
        $this->assertSame('Not installed', $checkResultsByLabel['Vendor_NotInstalledAnywhere']->getValue());
        $this->assertFalse($checkResultsByLabel['Vendor_NotInstalledAnywhere']->isValid());
    }
}
