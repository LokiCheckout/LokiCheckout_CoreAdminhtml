<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollectionFactory;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\DevModules;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Module\Manager as ModuleManager;
use PHPUnit\Framework\TestCase;

class DevModulesTest extends TestCase
{
    private const DEV_MODULES = [
        'LokiCheckout_Bundle',
        'LokiCheckout_DevTools',
        'LokiCheckout_FunctionalTests',
        'LokiCheckout_MollieDevTools',
        'Loki_Debugger',
    ];

    /**
     * @dataProvider getTestValues
     */
    public function testCheckResults(string $mode, bool $isEnabled, string $expectedValue, bool $expectedValid): void
    {
        $checkResults = $this->createDevModules($mode, $isEnabled)->getCheckResultCollection()->get();

        $this->assertCount(count(self::DEV_MODULES), $checkResults);
        foreach ($checkResults as $index => $checkResult) {
            $this->assertSame(self::DEV_MODULES[$index], $checkResult->getLabel());
            $this->assertSame($expectedValue, $checkResult->getValue());
            $this->assertSame($expectedValid, $checkResult->isValid());
            $this->assertNotEmpty($checkResult->getRecommendation());
            $this->assertNull($checkResult->getUrl());
        }
    }

    public static function getTestValues(): array
    {
        return [
            'production mode, enabled' => [AppState::MODE_PRODUCTION, true, 'Enabled', false],
            'production mode, disabled' => [AppState::MODE_PRODUCTION, false, 'Disabled', true],
            'developer mode, enabled' => [AppState::MODE_DEVELOPER, true, 'Enabled', true],
            'developer mode, disabled' => [AppState::MODE_DEVELOPER, false, 'Disabled', true],
            'default mode, enabled' => [AppState::MODE_DEFAULT, true, 'Enabled', true],
        ];
    }

    public function testCheckResultsAreNotDuplicatedWhenCalledTwice(): void
    {
        $devModules = $this->createDevModules(AppState::MODE_DEVELOPER, false);

        $devModules->getCheckResultCollection();
        $this->assertCount(count(self::DEV_MODULES), $devModules->getCheckResultCollection()->get());
    }

    private function createDevModules(string $mode, bool $isEnabled): DevModules
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturn($isEnabled);

        $appState = $this->createMock(AppState::class);
        $appState->method('getMode')->willReturn($mode);

        $checkResultCollectionFactory = $this->createMock(CheckResultCollectionFactory::class);
        $checkResultCollectionFactory->method('create')
            ->willReturnCallback(fn () => new CheckResultCollection());

        return new DevModules($moduleManager, $appState, $checkResultCollectionFactory);
    }
}
