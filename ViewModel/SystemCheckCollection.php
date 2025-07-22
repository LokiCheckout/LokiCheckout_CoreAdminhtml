<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;

class SystemCheckCollection implements ArgumentInterface
{
    public function __construct(
        private readonly array $systemChecks = []
    ) {
    }

    /**
     * @return SystemCheckInterface[]
     */
    public function getSystemChecks(): array
    {
        return $this->systemChecks;
    }
}
