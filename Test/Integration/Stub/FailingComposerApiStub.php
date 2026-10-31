<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Stub;

use LokiCheckout\Core\Util\Composer\ComposerApi;
use RuntimeException;

/**
 * Simulates an unreachable composer.yireo.com
 */
class FailingComposerApiStub extends ComposerApi
{
    public function __construct()
    {
    }

    public function getProjects(): array
    {
        throw new RuntimeException('cURL error 28: Connection timed out');
    }

    public function getPackages(): array
    {
        throw new RuntimeException('cURL error 28: Connection timed out');
    }
}
