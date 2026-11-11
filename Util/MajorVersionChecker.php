<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Util;

use LokiCheckout\Core\Util\Composer\ComposerApi;
use LokiCheckout\Core\Util\Composer\ComposerLock;
use LokiCheckout\CoreAdminhtml\ValueObject\MajorVersionUpdate;

class MajorVersionChecker
{
    public function __construct(
        private readonly ComposerLock $composerLock,
        private readonly ComposerApi $composerApi,
        private readonly MajorVersion $majorVersion,
    ) {
    }

    /**
     * @return MajorVersionUpdate[]
     */
    public function getMajorUpdates(): array
    {
        $lockData = $this->composerLock->getComposerLockData();
        $availablePackages = $this->composerApi->getPackages();

        $updates = [];
        foreach ($lockData['packages'] ?? [] as $installedPackage) {
            $composerName = (string)($installedPackage['name'] ?? '');
            if ($composerName === '' || !isset($availablePackages[$composerName])) {
                continue;
            }

            $installedVersion = ltrim((string)($installedPackage['version'] ?? ''), 'vV');
            if (!$this->isReleaseVersion($installedVersion)) {
                continue;
            }

            $latestVersion = $this->getLatestStableVersion((array)$availablePackages[$composerName]);
            if ($latestVersion === '') {
                continue;
            }

            if (!$this->majorVersion->isNewer($latestVersion, $installedVersion)) {
                continue;
            }

            $updates[] = new MajorVersionUpdate($composerName, $installedVersion, $latestVersion);
        }

        return $updates;
    }

    private function getLatestStableVersion(array $releases): string
    {
        $versions = [];
        foreach ($releases as $key => $release) {
            $version = ltrim((string)($release['version'] ?? $key), 'vV');
            if ($this->isStableVersion($version)) {
                $versions[] = $version;
            }
        }

        if (empty($versions)) {
            return '';
        }

        usort($versions, 'version_compare');
        return (string)array_pop($versions);
    }

    private function isReleaseVersion(string $version): bool
    {
        return (bool)preg_match('/^\d+(\.\d+)*(-?(p|patch|alpha|beta|rc|a|b)\.?\d*)?$/i', $version);
    }

    private function isStableVersion(string $version): bool
    {
        return (bool)preg_match('/^\d+(\.\d+)*(-?(p|patch)\.?\d*)?$/i', $version);
    }
}
