<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ValueObject;

use LokiCheckout\Core\Config\LokiCheckoutModules;
use LokiCheckout\Core\Util\Composer\ComposerApi;
use LokiCheckout\Core\Util\Composer\ComposerPackage;
use LokiCheckout\CoreAdminhtml\ValueObject\GitInfo;
use LokiCheckout\CoreAdminhtml\ValueObject\GitInfoFactory;
use LokiCheckout\CoreAdminhtml\ValueObject\Module;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\Manager as ModuleManager;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testGetters(): void
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->with('LokiCheckout_Foo')->willReturn(true);

        $lokiCheckoutModules = $this->createMock(LokiCheckoutModules::class);
        $lokiCheckoutModules->method('hasModule')->with('LokiCheckout_Foo')->willReturn(true);

        $composerApi = $this->createMock(ComposerApi::class);
        $composerApi->method('getLatestCommit')->with('loki-checkout/foo')->willReturn('latest-commit');
        $composerApi->method('getAvailableVersion')->with('loki-checkout/foo')->willReturn('1.2.4');
        $composerApi->method('getAvailableVersionDate')->with('loki-checkout/foo')->willReturn('2026-01-02 03:04:05');

        $module = $this->createModule($moduleManager, $lokiCheckoutModules, $composerApi);

        $this->assertSame('LokiCheckout_Foo', $module->getName());
        $this->assertTrue($module->isEnabled());
        $this->assertTrue($module->isLokiCheckoutRegistered());
        $this->assertSame('loki-checkout/foo', $module->getComposerName());
        $this->assertSame('1.2.3', $module->getComposerVersion());
        $this->assertSame('2026-01-01', $module->getDate());
        $this->assertSame('latest-commit', $module->getLatestCommit());
        $this->assertSame('1.2.4', $module->getLatestComposerVersion());
        $this->assertSame('2026-01-02 03:04:05', $module->getLatestModuleDate());
        $this->assertSame('/path/to/module', $module->getModulePath());
    }

    public function testDevVersionFollowsGitInfo(): void
    {
        $gitInfo = $this->createMock(GitInfo::class);
        $gitInfo->method('hasInfo')->willReturn(true);
        $module = $this->createModule(gitInfo: $gitInfo);
        $this->assertTrue($module->isDevVersion());
        $this->assertSame($gitInfo, $module->getGitInfo());

        $gitInfo = $this->createMock(GitInfo::class);
        $gitInfo->method('hasInfo')->willReturn(false);
        $module = $this->createModule(gitInfo: $gitInfo);
        $this->assertFalse($module->isDevVersion());
    }

    private function createModule(
        ?ModuleManager $moduleManager = null,
        ?LokiCheckoutModules $lokiCheckoutModules = null,
        ?ComposerApi $composerApi = null,
        ?GitInfo $gitInfo = null,
    ): Module {
        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')
            ->with(ComponentRegistrar::MODULE, 'LokiCheckout_Foo')
            ->willReturn('/path/to/module');

        $gitInfoFactory = $this->createMock(GitInfoFactory::class);
        $gitInfoFactory->method('create')
            ->with(['path' => '/path/to/module'])
            ->willReturn($gitInfo ?? $this->createMock(GitInfo::class));

        return new Module(
            $componentRegistrar,
            $moduleManager ?? $this->createMock(ModuleManager::class),
            $lokiCheckoutModules ?? $this->createMock(LokiCheckoutModules::class),
            $gitInfoFactory,
            $composerApi ?? $this->createMock(ComposerApi::class),
            'LokiCheckout_Foo',
            new ComposerPackage('LokiCheckout_Foo', 'loki-checkout/foo', '1.2.3', '2026-01-01'),
        );
    }
}
