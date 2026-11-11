<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ViewModel;

use LokiCheckout\Core\Config\LokiCheckoutModules;
use LokiCheckout\Core\Util\Composer\ComposerPackage;
use LokiCheckout\Core\Util\Composer\ComposerPackageFactory;
use LokiCheckout\CoreAdminhtml\Cron\MajorVersionCheck;
use LokiCheckout\CoreAdminhtml\Util\MajorVersion;
use LokiCheckout\CoreAdminhtml\Util\MajorVersionChecker;
use LokiCheckout\CoreAdminhtml\ValueObject\GitInfo;
use LokiCheckout\CoreAdminhtml\ValueObject\MajorVersionUpdate;
use LokiCheckout\CoreAdminhtml\ValueObject\Module;
use LokiCheckout\CoreAdminhtml\ValueObject\ModuleFactory;
use LokiCheckout\CoreAdminhtml\ViewModel\Modules;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Module\ModuleList\Loader as ModuleListLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModulesTest extends TestCase
{
    public function testGetModulesOnlyReturnsLokiModules(): void
    {
        $moduleListLoader = $this->createStub(ModuleListLoader::class);
        $moduleListLoader->method('load')->willReturn([
            'Magento_Catalog' => ['name' => 'Magento_Catalog'],
            'LokiCheckout_Foo' => ['name' => 'LokiCheckout_Foo'],
            'Loki_Bar' => ['name' => 'Loki_Bar'],
            'LokiTheme_LumaComponents' => ['name' => 'LokiTheme_LumaComponents'],
            'Vendor_Registered' => ['name' => 'Vendor_Registered'],
            'Vendor_Other' => ['name' => 'Vendor_Other'],
            'Vendor_LokiCheckoutIntegration' => ['name' => 'Vendor_LokiCheckoutIntegration'],
        ]);

        $lokiCheckoutModules = $this->createStub(LokiCheckoutModules::class);
        $lokiCheckoutModules->method('hasModule')
            ->willReturnCallback(fn (string $moduleName) => $moduleName === 'Vendor_Registered');

        $composerPackageFactory = $this->createStub(ComposerPackageFactory::class);
        $composerPackageFactory->method('createByModuleName')
            ->willReturnCallback(fn (string $moduleName) => new ComposerPackage($moduleName, '', '', ''));

        $createdModuleNames = [];
        $moduleFactory = $this->createStub(ModuleFactory::class);
        $moduleFactory->method('create')
            ->willReturnCallback(function (array $data) use (&$createdModuleNames) {
                $this->assertInstanceOf(ComposerPackage::class, $data['composerPackage']);
                $this->assertSame($data['moduleName'], $data['composerPackage']->getModuleName());
                $createdModuleNames[] = $data['moduleName'];
                return $this->createStub(Module::class);
            });

        $modules = new Modules(
            $moduleListLoader,
            $composerPackageFactory,
            $lokiCheckoutModules,
            $moduleFactory,
            $this->createStub(ScopeConfigInterface::class),
            new MajorVersion(),
            $this->createStub(MajorVersionChecker::class),
            $this->createStub(ModuleManager::class)
        );

        $this->assertCount(4, $modules->getModules());
        $this->assertSame(
            ['LokiCheckout_Foo', 'Loki_Bar', 'LokiTheme_LumaComponents', 'Vendor_Registered'],
            $createdModuleNames
        );
    }

    #[DataProvider('getShortenCommitValues')]
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

    #[DataProvider('getGitTextValues')]
    public function testGetGitText(string $commit, string $branch, string $expected): void
    {
        $gitInfo = $this->createStub(GitInfo::class);
        $gitInfo->method('getCommit')->willReturn($commit);
        $gitInfo->method('getBranch')->willReturn($branch);

        $this->assertSame($expected, $this->createModulesViewModel()->getGitText($gitInfo));
    }

    public static function getGitTextValues(): array
    {
        return [
            'commit and branch' => ['91ec086c0485cbe4891f8dfdb2bc2570becb9122', 'main', '91ec086c (main)'],
            'detached head' => ['91ec086c0485cbe4891f8dfdb2bc2570becb9122', '', '91ec086c'],
            'unresolvable commit' => ['', 'main', '(main)'],
            'nothing known' => ['', '', ''],
        ];
    }

    public function testGetModulesSurvivesBrokenComposerPackage(): void
    {
        $moduleListLoader = $this->createStub(ModuleListLoader::class);
        $moduleListLoader->method('load')->willReturn([
            'LokiCheckout_Broken' => ['name' => 'LokiCheckout_Broken'],
            'LokiCheckout_Foo' => ['name' => 'LokiCheckout_Foo'],
        ]);

        $composerPackageFactory = $this->createStub(ComposerPackageFactory::class);
        $composerPackageFactory->method('createByModuleName')->willReturnCallback(
            function (string $moduleName) {
                if ($moduleName === 'LokiCheckout_Broken') {
                    throw new \TypeError('Cannot access offset of type string on null');
                }

                return new ComposerPackage($moduleName, 'loki-checkout/foo', '1.0.0', '');
            }
        );

        $composerPackages = [];
        $moduleFactory = $this->createStub(ModuleFactory::class);
        $moduleFactory->method('create')->willReturnCallback(
            function (array $data) use (&$composerPackages) {
                $composerPackages[$data['moduleName']] = $data['composerPackage'];
                return $this->createStub(Module::class);
            }
        );

        $modules = new Modules(
            $moduleListLoader,
            $composerPackageFactory,
            $this->createStub(LokiCheckoutModules::class),
            $moduleFactory,
            $this->createStub(ScopeConfigInterface::class),
            new MajorVersion(),
            $this->createStub(MajorVersionChecker::class),
            $this->createStub(ModuleManager::class)
        );

        $this->assertCount(2, $modules->getModules());
        $this->assertSame('LokiCheckout_Broken', $composerPackages['LokiCheckout_Broken']->getModuleName());
        $this->assertSame('', $composerPackages['LokiCheckout_Broken']->getComposerName());
        $this->assertSame('loki-checkout/foo', $composerPackages['LokiCheckout_Foo']->getComposerName());
    }

    #[DataProvider('getLatestModuleVersionValues')]
    public function testGetLatestModuleVersion(
        bool $isDevVersion,
        string $latestCommit,
        string $latestComposerVersion,
        string $expected
    ): void {
        $module = $this->createStub(Module::class);
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
            'release version, latest is a commit' => [false, '', '0123456789abcdef0123456789abcdef01234567', '01234567'],
        ];
    }

    #[DataProvider('getLatestModuleVersionClassValues')]
    public function testGetLatestModuleVersionClass(
        bool $isDevVersion,
        string $currentCommit,
        string $latestCommit,
        string $composerVersion,
        string $latestComposerVersion,
        string $expected
    ): void {
        $gitInfo = $this->createStub(GitInfo::class);
        $gitInfo->method('getCommit')->willReturn($currentCommit);

        $module = $this->createStub(Module::class);
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
            'release version newer than latest' => [false, '', '', '1.2.5', '1.2.4', 'notice'],
            'release version with v-prefix' => [false, '', '', 'v1.2.3', '1.2.3', 'notice'],
            'release version, latest unknown' => [false, '', '', '1.2.3', '', 'warning'],
            'release version, both unknown' => [false, '', '', '', '', 'warning'],
            'dev version, latest commit unknown' => [true, 'abc', '', '', '', 'warning'],
            'current version unknown in composer.lock' => [false, '', '', 'unknown', '1.2.3', 'warning'],
            'current version is a dev branch' => [false, '', '', 'dev-main', '1.2.3', 'warning'],
            'current version is a branch alias' => [false, '', '', '1.0.x-dev', '1.2.3', 'warning'],
            'latest version is a commit' => [false, '', '', '1.2.3', '0123456789abcdef0123456789abcdef01234567', 'warning'],
            'patch release outdated' => [false, '', '', '2.4.8-p1', '2.4.8-p2', 'error'],
            'beta release up to date' => [false, '', '', '1.0.0-beta1', '1.0.0-beta1', 'notice'],
        ];
    }

    #[DataProvider('hasMajorVersionMismatchValues')]
    public function testHasMajorVersionMismatch(
        bool $isDevVersion,
        string $composerVersion,
        string $latestComposerVersion,
        bool $expected
    ): void {
        $module = $this->createModuleStub($isDevVersion, $composerVersion, $latestComposerVersion);

        $this->assertSame($expected, $this->createModulesViewModel()->hasMajorVersionMismatch($module));
    }

    public static function hasMajorVersionMismatchValues(): array
    {
        return [
            'older major installed' => [false, '1.2.3', '2.0.0', true],
            'newer major installed' => [false, '2.0.0', '1.9.9', true],
            'same major, older minor' => [false, '1.2.3', '1.9.0', false],
            'same version' => [false, '2.1.0', '2.1.0', false],
            'v-prefix' => [false, 'v1.0.0', '2.0.0', true],
            'patch release' => [false, '2.4.8-p1', '3.0.0', true],
            'major 0 to 1' => [false, '0.9.0', '1.0.0', true],
            '0.x minor bump' => [false, '0.4.3', '0.5.0', true],
            '0.x patch bump' => [false, '0.4.1', '0.4.9', false],
            'dev version' => [true, '1.0.0', '2.0.0', false],
            'dev branch' => [false, 'dev-main', '2.0.0', false],
            'branch alias' => [false, '1.0.x-dev', '2.0.0', false],
            'unknown current' => [false, 'unknown', '2.0.0', false],
            'empty latest' => [false, '1.0.0', '', false],
            'latest is a commit' => [false, '1.0.0', '0123456789abcdef0123456789abcdef01234567', false],
        ];
    }

    public function testGetMajorVersionMismatchModules(): void
    {
        $outdated = $this->createModuleStub(false, '1.0.0', '2.0.0');
        $upToDate = $this->createModuleStub(false, '2.0.0', '2.1.0');
        $devVersion = $this->createModuleStub(true, '1.0.0', '2.0.0');

        $result = $this->createModulesViewModel()->getMajorVersionMismatchModules([$upToDate, $outdated, $devVersion]);
        $this->assertSame([$outdated], $result);
    }

    #[DataProvider('getShowRegisteredValues')]
    public function testShowRegistered(mixed $configValue, bool $expected): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->any())->method('getValue')
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

    public function testGetMajorVersionUpdatesWhenAdminNotificationDisabled(): void
    {
        $updates = [new MajorVersionUpdate('vendor/foo', '1.0.0', '2.0.0')];
        $checker = $this->createStub(MajorVersionChecker::class);
        $checker->method('getMajorUpdates')->willReturn($updates);

        $viewModel = $this->createModulesViewModel($this->createConfigStub(true), $checker, false);
        $this->assertSame($updates, $viewModel->getMajorVersionUpdates());
    }

    public function testGetMajorVersionUpdatesEmptyWhenAdminNotificationEnabled(): void
    {
        $checker = $this->createMock(MajorVersionChecker::class);
        $checker->expects($this->never())->method('getMajorUpdates');

        $viewModel = $this->createModulesViewModel($this->createConfigStub(true), $checker, true);
        $this->assertSame([], $viewModel->getMajorVersionUpdates());
    }

    public function testGetMajorVersionUpdatesEmptyWhenConfigDisabled(): void
    {
        $checker = $this->createMock(MajorVersionChecker::class);
        $checker->expects($this->never())->method('getMajorUpdates');

        $viewModel = $this->createModulesViewModel($this->createConfigStub(false), $checker, false);
        $this->assertSame([], $viewModel->getMajorVersionUpdates());
    }

    public function testGetMajorVersionUpdatesEmptyWhenApiFails(): void
    {
        $checker = $this->createStub(MajorVersionChecker::class);
        $checker->method('getMajorUpdates')->willThrowException(new RuntimeException('API down'));

        $viewModel = $this->createModulesViewModel($this->createConfigStub(true), $checker, false);
        $this->assertSame([], $viewModel->getMajorVersionUpdates());
    }

    private function createConfigStub(bool $majorVersionCheck): ScopeConfigInterface
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->any())->method('isSetFlag')
            ->with(MajorVersionCheck::CONFIG_PATH)
            ->willReturn($majorVersionCheck);

        return $scopeConfig;
    }

    private function createModuleStub(bool $isDevVersion, string $composerVersion, string $latestComposerVersion): Module
    {
        $module = $this->createStub(Module::class);
        $module->method('isDevVersion')->willReturn($isDevVersion);
        $module->method('getComposerVersion')->willReturn($composerVersion);
        $module->method('getLatestComposerVersion')->willReturn($latestComposerVersion);

        return $module;
    }

    private function createModulesViewModel(
        ?ScopeConfigInterface $scopeConfig = null,
        ?MajorVersionChecker $majorVersionChecker = null,
        bool $adminNotificationEnabled = true
    ): Modules {
        $moduleManager = $this->createStub(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturn($adminNotificationEnabled);

        return new Modules(
            $this->createStub(ModuleListLoader::class),
            $this->createStub(ComposerPackageFactory::class),
            $this->createStub(LokiCheckoutModules::class),
            $this->createStub(ModuleFactory::class),
            $scopeConfig ?? $this->createStub(ScopeConfigInterface::class),
            new MajorVersion(),
            $majorVersionChecker ?? $this->createStub(MajorVersionChecker::class),
            $moduleManager
        );
    }
}
