<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Stub;

use LokiCheckout\Core\Util\Composer\ComposerApi;

/**
 * Avoids HTTP requests to composer.yireo.com during integration tests
 */
class ComposerApiStub extends ComposerApi
{
    public const COMPOSER_NAME = 'loki-checkout/magento2-core-adminhtml';
    public const LATEST_VERSION = '99.0.0';
    public const LATEST_COMMIT = '0123456789abcdef0123456789abcdef01234567';

    public function getProjects(): array
    {
        return [
            [
                'composer_name' => self::COMPOSER_NAME,
                'last_commit' => self::LATEST_COMMIT,
                'versions' => [self::LATEST_VERSION => []],
            ],
        ];
    }

    public function getPackages(): array
    {
        return [
            self::COMPOSER_NAME => [
                self::LATEST_VERSION => [
                    'version' => self::LATEST_VERSION,
                    'time' => '2026-01-01T00:00:00+00:00',
                ],
            ],
        ];
    }
}
