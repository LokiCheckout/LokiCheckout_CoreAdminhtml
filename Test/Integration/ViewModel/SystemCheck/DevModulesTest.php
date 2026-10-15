<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\DevModules;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

#[AppArea('adminhtml')]
class DevModulesTest extends TestCase
{
    public function testAllDevModulesAreValidOutsideProductionMode(): void
    {
        $objectManager = ObjectManager::getInstance();
        $this->assertNotSame(AppState::MODE_PRODUCTION, $objectManager->get(AppState::class)->getMode());

        $checkResults = $objectManager->create(DevModules::class)->getCheckResultCollection()->get();
        $this->assertCount(5, $checkResults);
        foreach ($checkResults as $checkResult) {
            $this->assertTrue($checkResult->isValid(), $checkResult->getLabel());
            $this->assertContains($checkResult->getValue(), ['Enabled', 'Disabled']);
        }
    }
}
