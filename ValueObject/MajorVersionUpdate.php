<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ValueObject;

class MajorVersionUpdate
{
    public function __construct(
        private readonly string $composerName,
        private readonly string $installedVersion,
        private readonly string $latestVersion,
    ) {
    }

    public function getComposerName(): string
    {
        return $this->composerName;
    }

    public function getInstalledVersion(): string
    {
        return $this->installedVersion;
    }

    public function getLatestVersion(): string
    {
        return $this->latestVersion;
    }
}
