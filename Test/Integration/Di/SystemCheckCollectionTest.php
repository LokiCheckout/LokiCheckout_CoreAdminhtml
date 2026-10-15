<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Di;

use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\AddressLines;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\CspMode;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\DevModules;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\MerchantVatNumber;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\ModulesEnabled;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\StoreCountryId;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheckCollection;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

#[AppArea('adminhtml')]
class SystemCheckCollectionTest extends TestCase
{
    public function testSystemChecksAreConfigured(): void
    {
        $systemCheckCollection = ObjectManager::getInstance()->create(SystemCheckCollection::class);
        $systemChecks = $systemCheckCollection->getSystemChecks();

        $expectedSystemChecks = [
            'modules_enabled' => ModulesEnabled::class,
            'dev_modules' => DevModules::class,
            'store_country_id' => StoreCountryId::class,
            'merchant_vat_number' => MerchantVatNumber::class,
            'address_lines' => AddressLines::class,
            'csp' => CspMode::class,
        ];

        $this->assertSame(array_keys($expectedSystemChecks), array_keys($systemChecks));
        foreach ($expectedSystemChecks as $name => $className) {
            $this->assertInstanceOf($className, $systemChecks[$name]);
            $this->assertInstanceOf(SystemCheckInterface::class, $systemChecks[$name]);
        }
    }
}
