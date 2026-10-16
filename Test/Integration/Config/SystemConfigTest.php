<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Config;

use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

#[AppArea('adminhtml')]
class SystemConfigTest extends TestCase
{
    public function testShowRegisteredIsOnlyConfigurableOnDefaultScope(): void
    {
        /** @var Field $field */
        $field = ObjectManager::getInstance()->get(Structure::class)
            ->getElement('loki_checkout/module_overview/show_registered');

        $this->assertInstanceOf(Field::class, $field);
        $this->assertTrue($field->showInDefault());
        $this->assertFalse($field->showInWebsite(), 'Value is only read from the default scope');
        $this->assertFalse($field->showInStore(), 'Value is only read from the default scope');
    }
}
