<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Stub;

use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\AddressLines;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheckCollection;
use Psr\Log\LoggerInterface;

class SystemCheckCollectionWithFailingCheck extends SystemCheckCollection
{
    public function __construct(
        LoggerInterface $logger,
        FailingSystemCheckStub $failingSystemCheck,
        AddressLines $addressLines
    ) {
        parent::__construct($logger, [
            'failing_check' => $failingSystemCheck,
            'address_lines' => $addressLines,
        ]);
    }
}
