<?php
declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel;

use LokiCheckout\CoreAdminhtml\Cron\MajorVersionCheck;
use LokiCheckout\CoreAdminhtml\Util\MajorVersion;
use LokiCheckout\CoreAdminhtml\Util\MajorVersionChecker;
use LokiCheckout\CoreAdminhtml\ValueObject\GitInfo;
use LokiCheckout\CoreAdminhtml\ValueObject\MajorVersionUpdate;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Module\ModuleList\Loader as ModuleListLoader;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use LokiCheckout\Core\Config\LokiCheckoutModules;
use LokiCheckout\Core\Util\Composer\ComposerPackage;
use LokiCheckout\Core\Util\Composer\ComposerPackageFactory;
use LokiCheckout\CoreAdminhtml\ValueObject\Module;
use LokiCheckout\CoreAdminhtml\ValueObject\ModuleFactory;
use Throwable;

class Modules implements ArgumentInterface
{
    public function __construct(
        private readonly ModuleListLoader $moduleListLoader,
        private readonly ComposerPackageFactory $composerPackageFactory,
        private readonly LokiCheckoutModules $lokiCheckoutModules,
        private readonly ModuleFactory $moduleFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly MajorVersion $majorVersion,
        private readonly MajorVersionChecker $majorVersionChecker,
        private readonly ModuleManager $moduleManager,
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
            if (false === str_starts_with($moduleName, 'Loki')
                && false === $isLokiCheckoutRegistered
            ) {
                continue;
            }

            $modules[] = $this->moduleFactory->create([
                'moduleName' => $moduleName,
                'composerPackage' => $this->createComposerPackage($moduleName),
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
        if ($this->isCommit($latestComposerVersion)) {
            return $this->shortenCommit($latestComposerVersion);
        }

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
        if (!$this->isReleaseVersion($composerVersion) || !$this->isReleaseVersion($latestComposerVersion)) {
            return 'warning';
        }

        if (version_compare($composerVersion, $latestComposerVersion, '>=')) {
            return 'notice';
        }

        return 'error';
    }

    public function hasMajorVersionMismatch(Module $module): bool
    {
        if ($module->isDevVersion()) {
            return false;
        }

        $composerVersion = ltrim($module->getComposerVersion(), 'vV');
        $latestComposerVersion = ltrim($module->getLatestComposerVersion(), 'vV');
        if (!$this->isReleaseVersion($composerVersion) || !$this->isReleaseVersion($latestComposerVersion)) {
            return false;
        }

        return $this->majorVersion->isDifferent($composerVersion, $latestComposerVersion);
    }

    /**
     * @param Module[] $modules
     * @return Module[]
     */
    public function getMajorVersionMismatchModules(array $modules): array
    {
        return array_values(array_filter(
            $modules,
            fn (Module $module) => $this->hasMajorVersionMismatch($module)
        ));
    }

    /**
     * @return MajorVersionUpdate[]
     */
    public function getMajorVersionUpdates(): array
    {
        if ($this->moduleManager->isEnabled('Magento_AdminNotification')) {
            return [];
        }

        if (!$this->scopeConfig->isSetFlag(MajorVersionCheck::CONFIG_PATH)) {
            return [];
        }

        try {
            return $this->majorVersionChecker->getMajorUpdates();
        } catch (Throwable) {
            return [];
        }
    }

    public function getGitText(GitInfo $gitInfo): string
    {
        $parts = [];
        if ($gitInfo->getCommit() !== '') {
            $parts[] = $this->shortenCommit($gitInfo->getCommit());
        }

        if ($gitInfo->getBranch() !== '') {
            $parts[] = '(' . $gitInfo->getBranch() . ')';
        }

        return implode(' ', $parts);
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

    private function createComposerPackage(string $moduleName): ComposerPackage
    {
        try {
            return $this->composerPackageFactory->createByModuleName($moduleName);
        } catch (Throwable) {
            return new ComposerPackage($moduleName, '', '', '');
        }
    }

    private function isReleaseVersion(string $version): bool
    {
        return (bool)preg_match('/^\d+(\.\d+)*(-?(p|patch|alpha|beta|rc|a|b)\.?\d*)?$/i', $version);
    }

    private function isCommit(string $version): bool
    {
        return (bool)preg_match('/^[0-9a-f]{40}$/', $version);
    }
}
