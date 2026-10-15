<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;

class DefaultConfigTest extends TestCase
{
    public function testShowRegisteredIsDisabledByDefault(): void
    {
        $scopeConfig = ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->assertSame('0', (string)$scopeConfig->getValue('loki_checkout/module_overview/show_registered'));
    }
}
