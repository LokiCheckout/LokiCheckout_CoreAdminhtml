<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ViewModel;

use LokiCheckout\Core\Config\LokiCheckoutModules;
use LokiCheckout\Core\Util\Composer\ComposerPackage;
use LokiCheckout\Core\Util\Composer\ComposerPackageFactory;
use LokiCheckout\CoreAdminhtml\ValueObject\GitInfo;
use LokiCheckout\CoreAdminhtml\ValueObject\Module;
use LokiCheckout\CoreAdminhtml\ValueObject\ModuleFactory;
use LokiCheckout\CoreAdminhtml\ViewModel\Modules;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleList\Loader as ModuleListLoader;
use PHPUnit\Framework\TestCase;

class ModulesTest extends TestCase
{
    public function testGetModulesOnlyReturnsLokiModules(): void
    {
        $moduleListLoader = $this->createMock(ModuleListLoader::class);
        $moduleListLoader->method('load')->willReturn([
            'Magento_Catalog' => ['name' => 'Magento_Catalog'],
            'LokiCheckout_Foo' => ['name' => 'LokiCheckout_Foo'],
            'Loki_Bar' => ['name' => 'Loki_Bar'],
            'Vendor_Registered' => ['name' => 'Vendor_Registered'],
            'Vendor_Other' => ['name' => 'Vendor_Other'],
        ]);

        $lokiCheckoutModules = $this->createMock(LokiCheckoutModules::class);
        $lokiCheckoutModules->method('hasModule')
            ->willReturnCallback(fn (string $moduleName) => $moduleName === 'Vendor_Registered');

        $composerPackageFactory = $this->createMock(ComposerPackageFactory::class);
        $composerPackageFactory->method('createByModuleName')
            ->willReturnCallback(fn (string $moduleName) => new ComposerPackage($moduleName, '', '', ''));

        $createdModuleNames = [];
        $moduleFactory = $this->createMock(ModuleFactory::class);
        $moduleFactory->method('create')
            ->willReturnCallback(function (array $data) use (&$createdModuleNames) {
                $this->assertInstanceOf(ComposerPackage::class, $data['composerPackage']);
                $this->assertSame($data['moduleName'], $data['composerPackage']->getModuleName());
                $createdModuleNames[] = $data['moduleName'];
                return $this->createMock(Module::class);
            });

        $modules = new Modules(
            $moduleListLoader,
            $composerPackageFactory,
            $lokiCheckoutModules,
            $moduleFactory,
            $this->createMock(ScopeConfigInterface::class)
        );

        $this->assertCount(3, $modules->getModules());
        $this->assertSame(['LokiCheckout_Foo', 'Loki_Bar', 'Vendor_Registered'], $createdModuleNames);
    }

    /**
     * @dataProvider getShortenCommitValues
     */
    public function testShortenCommit(string $commit, string $expected): void
    {
        $this->assertSame($expected, $this->createModulesViewModel()->shortenCommit($commit));
    }

    public static function getShortenCommitValues(): array
    {
        return [
            ['0123456789abcdef0123456789abcdef01234567', '01234567'],
            ['91ec086c0485cbe4891f8dfdb2bc2570becb9122', '91ec086c'],
            ['abcdef1234', 'abcdef12'],
            ['abc', 'abc'],
            ['12345678', '12345678'],
            ['', ''],
        ];
    }

    public function testGetGitText(): void
    {
        $gitInfo = $this->createMock(GitInfo::class);
        $gitInfo->method('getCommit')->willReturn('91ec086c0485cbe4891f8dfdb2bc2570becb9122');
        $gitInfo->method('getBranch')->willReturn('main');

        $this->assertSame('91ec086c (main)', $this->createModulesViewModel()->getGitText($gitInfo));
    }

    /**
     * @dataProvider getLatestModuleVersionValues
     */
    public function testGetLatestModuleVersion(
        bool $isDevVersion,
        string $latestCommit,
        string $latestComposerVersion,
        string $expected
    ): void {
        $module = $this->createMock(Module::class);
        $module->method('isDevVersion')->willReturn($isDevVersion);
        $module->method('getLatestCommit')->willReturn($latestCommit);
        $module->method('getLatestComposerVersion')->willReturn($latestComposerVersion);

        $this->assertSame($expected, $this->createModulesViewModel()->getLatestModuleVersion($module));
    }

    public static function getLatestModuleVersionValues(): array
    {
        return [
            'dev version with commit' => [true, '0123456789abcdef0123456789abcdef01234567', '1.2.3', '01234567'],
            'dev version without commit' => [true, '', '1.2.3', '1.2.3'],
            'release version' => [false, '0123456789abcdef', '1.2.3', '1.2.3'],
            'unknown version' => [false, '', '', 'unknown'],
        ];
    }

    /**
     * @dataProvider getLatestModuleVersionClassValues
     */
    public function testGetLatestModuleVersionClass(
        bool $isDevVersion,
        string $currentCommit,
        string $latestCommit,
        string $composerVersion,
        string $latestComposerVersion,
        string $expected
    ): void {
        $gitInfo = $this->createMock(GitInfo::class);
        $gitInfo->method('getCommit')->willReturn($currentCommit);

        $module = $this->createMock(Module::class);
        $module->method('isDevVersion')->willReturn($isDevVersion);
        $module->method('getGitInfo')->willReturn($gitInfo);
        $module->method('getLatestCommit')->willReturn($latestCommit);
        $module->method('getComposerVersion')->willReturn($composerVersion);
        $module->method('getLatestComposerVersion')->willReturn($latestComposerVersion);

        $this->assertSame($expected, $this->createModulesViewModel()->getLatestModuleVersionClass($module));
    }

    public static function getLatestModuleVersionClassValues(): array
    {
        return [
            'dev version up to date' => [true, 'abc', 'abc', '', '', 'notice'],
            'dev version outdated' => [true, 'abc', 'def', '', '', 'warning'],
            'release version up to date' => [false, '', '', '1.2.3', '1.2.3', 'notice'],
            'release version outdated' => [false, '', '', '1.2.3', '1.2.4', 'error'],
        ];
    }

    /**
     * @dataProvider getShowRegisteredValues
     */
    public function testShowRegistered(mixed $configValue, bool $expected): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with('loki_checkout/module_overview/show_registered')
            ->willReturn($configValue);

        $this->assertSame($expected, $this->createModulesViewModel($scopeConfig)->showRegistered());
    }

    public static function getShowRegisteredValues(): array
    {
        return [
            ['1', true],
            ['0', false],
            [null, false],
        ];
    }

    private function createModulesViewModel(?ScopeConfigInterface $scopeConfig = null): Modules
    {
        return new Modules(
            $this->createMock(ModuleListLoader::class),
            $this->createMock(ComposerPackageFactory::class),
            $this->createMock(LokiCheckoutModules::class),
            $this->createMock(ModuleFactory::class),
            $scopeConfig ?? $this->createMock(ScopeConfigInterface::class)
        );
    }
}
