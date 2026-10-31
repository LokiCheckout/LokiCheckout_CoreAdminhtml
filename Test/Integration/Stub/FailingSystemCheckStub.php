<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Stub;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;
use RuntimeException;

class FailingSystemCheckStub implements SystemCheckInterface
{
    public const MESSAGE = 'Third-party system check exploded';

    public function getCheckResultCollection(): CheckResultCollection
    {
        throw new RuntimeException(self::MESSAGE);
    }

    public function getUrl(): string|null
    {
        return null;
    }
}
