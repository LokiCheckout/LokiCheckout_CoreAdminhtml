<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ViewModel\SystemCheck;

use LokiCheckout\Core\Config\LokiCheckoutModules;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollectionFactory;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\ModulesEnabled;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Module\ModuleList;
use PHPUnit\Framework\TestCase;

class ModulesEnabledTest extends TestCase
{
    public function testDependenciesOfLokiModulesAreChecked(): void
    {
        $checkResults = $this->getCheckResults([
            'Magento_Catalog' => ['name' => 'Magento_Catalog', 'sequence' => ['Magento_Eav']],
            'LokiCheckout_Foo' => ['name' => 'LokiCheckout_Foo', 'sequence' => ['Loki_Components', 'Vendor_Disabled']],
            'Loki_Components' => ['name' => 'Loki_Components', 'sequence' => ['Magento_Backend']],
            'LokiTheme_Bar' => ['name' => 'LokiTheme_Bar', 'sequence' => ['Loki_Components']],
        ]);

        $this->assertSame(['Loki_Components', 'Vendor_Disabled', 'Magento_Backend'], array_keys($checkResults));

        $this->assertSame('Enabled', $checkResults['Loki_Components']->getValue());
        $this->assertTrue($checkResults['Loki_Components']->isValid());
        $this->assertStringContainsString('LokiCheckout_Foo, LokiTheme_Bar', $checkResults['Loki_Components']->getRecommendation());

        $this->assertSame('Disabled', $checkResults['Vendor_Disabled']->getValue());
        $this->assertFalse($checkResults['Vendor_Disabled']->isValid());
    }

    public function testDependencyThatIsNotInstalledIsReportedAsNotInstalled(): void
    {
        $checkResults = $this->getCheckResults([
            'LokiCheckout_Foo' => ['name' => 'LokiCheckout_Foo', 'sequence' => ['Vendor_Missing', 'Vendor_Disabled']],
        ]);

        $this->assertSame('Not installed', $checkResults['Vendor_Missing']->getValue());
        $this->assertFalse($checkResults['Vendor_Missing']->isValid());
        $this->assertFalse($checkResults['Vendor_Missing']->isWarning());
        $this->assertStringContainsString('LokiCheckout_Foo', $checkResults['Vendor_Missing']->getRecommendation());

        $this->assertSame('Disabled', $checkResults['Vendor_Disabled']->getValue());
    }

    /**
     * @return array<string, CheckResult>
     */
    private function getCheckResults(array $modules): array
    {
        $moduleList = $this->createStub(ModuleList::class);
        $moduleList->method('getAll')->willReturn($modules);

        $moduleManager = $this->createStub(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturnCallback(
            fn (string $moduleName) => !in_array($moduleName, ['Vendor_Disabled', 'Vendor_Missing'], true)
        );

        $componentRegistrar = $this->createStub(ComponentRegistrarInterface::class);
        $componentRegistrar->method('getPath')->willReturnCallback(
            fn (string $type, string $moduleName) => $type === ComponentRegistrar::MODULE && $moduleName !== 'Vendor_Missing'
                ? '/path/to/' . $moduleName
                : null
        );

        $lokiCheckoutModules = $this->createStub(LokiCheckoutModules::class);
        $lokiCheckoutModules->method('seemsModule')
            ->willReturnCallback(fn (string $moduleName) => str_contains($moduleName, 'LokiCheckout'));

        $checkResultCollectionFactory = $this->createStub(CheckResultCollectionFactory::class);
        $checkResultCollectionFactory->method('create')
            ->willReturnCallback(fn () => new CheckResultCollection());

        $modulesEnabled = new ModulesEnabled(
            $moduleList,
            $moduleManager,
            $lokiCheckoutModules,
            $componentRegistrar,
            $checkResultCollectionFactory
        );

        $checkResults = [];
        foreach ($modulesEnabled->getCheckResultCollection()->get() as $checkResult) {
            $checkResults[$checkResult->getLabel()] = $checkResult;
        }

        return $checkResults;
    }
}
