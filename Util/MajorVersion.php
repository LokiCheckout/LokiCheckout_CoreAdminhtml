<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Util;

class MajorVersion
{
    public function getKey(string $version): string
    {
        $parts = explode('.', ltrim($version, 'vV'));
        $major = (int)$parts[0];
        if ($major === 0) {
            return '0.' . (int)($parts[1] ?? 0);
        }

        return (string)$major;
    }

    public function isNewer(string $version, string $compareVersion): bool
    {
        return version_compare($this->getKey($version), $this->getKey($compareVersion), '>');
    }

    public function isDifferent(string $version, string $compareVersion): bool
    {
        return $this->getKey($version) !== $this->getKey($compareVersion);
    }
}
