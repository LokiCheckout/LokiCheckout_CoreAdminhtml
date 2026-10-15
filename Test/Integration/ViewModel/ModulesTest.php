<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\ViewModel;

use LokiCheckout\Core\Util\Composer\ComposerApi;
use LokiCheckout\CoreAdminhtml\Test\Integration\Stub\ComposerApiStub;
use LokiCheckout\CoreAdminhtml\ValueObject\Module;
use LokiCheckout\CoreAdminhtml\ViewModel\Modules;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

#[AppArea('adminhtml')]
class ModulesTest extends TestCase
{
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;
        $this->objectManager->addSharedInstance(new ComposerApiStub(), ComposerApi::class);
    }

    protected function tearDown(): void
    {
        $this->objectManager->removeSharedInstance(ComposerApi::class);
    }

    public function testGetModulesOnlyReturnsLokiModules(): void
    {
        $modules = $this->objectManager->create(Modules::class)->getModules();
        $this->assertNotEmpty($modules);

        $moduleNames = array_map(fn (Module $module) => $module->getName(), $modules);
        $this->assertContains('LokiCheckout_CoreAdminhtml', $moduleNames);
        $this->assertContains('LokiCheckout_Core', $moduleNames);
        foreach ($moduleNames as $moduleName) {
            $this->assertStringNotContainsString('Magento_', $moduleName);
        }
    }

    public function testOwnModuleDetails(): void
    {
        $viewModel = $this->objectManager->create(Modules::class);
        $module = $this->getModule($viewModel, 'LokiCheckout_CoreAdminhtml');

        $this->assertTrue($module->isEnabled());
        $this->assertSame(ComposerApiStub::COMPOSER_NAME, $module->getComposerName());
        $this->assertNotEmpty($module->getModulePath());
        $this->assertContains($viewModel->getLatestModuleVersionClass($module), ['notice', 'warning', 'error']);

        $expectedVersion = $module->isDevVersion()
            ? $viewModel->shortenCommit(ComposerApiStub::LATEST_COMMIT)
            : ComposerApiStub::LATEST_VERSION;
        $this->assertSame($expectedVersion, $viewModel->getLatestModuleVersion($module));
    }

    #[ConfigFixture('loki_checkout/module_overview/show_registered', '0', 'default')]
    public function testShowRegisteredDisabled(): void
    {
        $this->assertFalse($this->objectManager->create(Modules::class)->showRegistered());
    }

    #[ConfigFixture('loki_checkout/module_overview/show_registered', '1', 'default')]
    public function testShowRegisteredEnabled(): void
    {
        $this->assertTrue($this->objectManager->create(Modules::class)->showRegistered());
    }

    private function getModule(Modules $viewModel, string $moduleName): Module
    {
        foreach ($viewModel->getModules() as $module) {
            if ($module->getName() === $moduleName) {
                return $module;
            }
        }

        $this->fail('Module "' . $moduleName . '" not found');
    }
}
