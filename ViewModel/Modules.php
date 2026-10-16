<?php
declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel;

use LokiCheckout\CoreAdminhtml\ValueObject\GitInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\ModuleList\Loader as ModuleListLoader;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use LokiCheckout\Core\Config\LokiCheckoutModules;
use LokiCheckout\Core\Util\Composer\ComposerPackageFactory;
use LokiCheckout\CoreAdminhtml\ValueObject\Module;
use LokiCheckout\CoreAdminhtml\ValueObject\ModuleFactory;

class Modules implements ArgumentInterface
{
    public function __construct(
        private readonly ModuleListLoader $moduleListLoader,
        private readonly ComposerPackageFactory $composerPackageFactory,
        private readonly LokiCheckoutModules $lokiCheckoutModules,
        private readonly ModuleFactory $moduleFactory,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * @return Module[]
     * @throws LocalizedException
     */
    public function getModules(): array
    {
        $modules = [];
        foreach ($this->moduleListLoader->load() as $module) {
            $moduleName = $module['name'];
            $isLokiCheckoutRegistered = $this->lokiCheckoutModules->hasModule(
                $moduleName
            );
            if (false === str_contains($moduleName, 'LokiCheckout_')
                && false === str_contains($moduleName, 'Loki_')
                && false === $isLokiCheckoutRegistered
            ) {
                continue;
            }

            $composerPackage
                = $this->composerPackageFactory->createByModuleName(
                $moduleName
            );
            $modules[] = $this->moduleFactory->create([
                'moduleName' => $moduleName,
                'composerPackage' => $composerPackage,
            ]);
        }

        return $modules;
    }

    public function getLatestModuleVersion(Module $module): string
    {
        if ($module->isDevVersion()) {
            $latestCommit = $module->getLatestCommit();
            if (!empty($latestCommit)) {
                return $this->shortenCommit($latestCommit);
            }
        }

        $latestComposerVersion = $module->getLatestComposerVersion();
        if (!empty($latestComposerVersion)) {
            return $latestComposerVersion;
        }

        return 'unknown';
    }

    public function getLatestModuleVersionClass(Module $module): string
    {
        if ($module->isDevVersion()) {
            $latestCommit = $module->getLatestCommit();
            if ($latestCommit !== '' && $module->getGitInfo()->getCommit() === $latestCommit) {
                return 'notice';
            }

            return 'warning';
        }

        $composerVersion = ltrim($module->getComposerVersion(), 'vV');
        $latestComposerVersion = ltrim($module->getLatestComposerVersion(), 'vV');
        if ($composerVersion === '' || $latestComposerVersion === '') {
            return 'warning';
        }

        if (version_compare($composerVersion, $latestComposerVersion, '>=')) {
            return 'notice';
        }

        return 'error';
    }

    public function getGitText(GitInfo $gitInfo): string
    {
        return $this->shortenCommit($gitInfo->getCommit()) . ' (' . $gitInfo->getBranch() . ')';
    }

    public function shortenCommit(string $gitCommit): string
    {
        return substr($gitCommit, 0, 8);
    }

    public function showRegistered(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'loki_checkout/module_overview/show_registered'
        );
    }
}
