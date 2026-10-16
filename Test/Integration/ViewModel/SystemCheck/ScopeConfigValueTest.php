<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\AddressLines;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\CspMode;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\MerchantVatNumber;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\StoreCountryId;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use PHPUnit\Framework\TestCase;

/**
 * Note that the ConfigFixture of the testing framework does not cascade values from the default scope to the
 * store views, while the system checks read the values per store view. Therefore, the fixtures set store values.
 */
#[AppArea('adminhtml')]
class ScopeConfigValueTest extends TestCase
{
    #[ConfigFixture('customer/address/street_lines', '2', 'store', 'default')]
    public function testAddressLinesInvalid(): void
    {
        $checkResult = $this->getCheckResult(AddressLines::class);
        $this->assertSame('customer/address/street_lines', $checkResult->getLabel());
        $this->assertFalse($checkResult->isValid());
        $this->assertStringContainsString('section/customer', (string)$checkResult->getUrl());
        $this->assertStringEndsWith('#customer_address', (string)$checkResult->getUrl());
    }

    #[ConfigFixture('customer/address/street_lines', '3', 'store', 'default')]
    public function testAddressLinesValid(): void
    {
        $this->assertTrue($this->getCheckResult(AddressLines::class)->isValid());
    }

    #[ConfigFixture('general/store_information/merchant_vat_number', '', 'store', 'default')]
    public function testMerchantVatNumberInvalid(): void
    {
        $this->assertFalse($this->getCheckResult(MerchantVatNumber::class)->isValid());
    }

    #[ConfigFixture('general/store_information/merchant_vat_number', 'NL123456789B01', 'store', 'default')]
    public function testMerchantVatNumberValid(): void
    {
        $checkResult = $this->getCheckResult(MerchantVatNumber::class);
        $this->assertTrue($checkResult->isValid());
        $this->assertSame('NL123456789B01', $checkResult->getValue());
    }

    #[ConfigFixture('general/store_information/country_id', '', 'store', 'default')]
    public function testStoreCountryIdInvalid(): void
    {
        $this->assertFalse($this->getCheckResult(StoreCountryId::class)->isValid());
    }

    #[ConfigFixture('general/store_information/country_id', 'NL', 'store', 'default')]
    public function testStoreCountryIdValid(): void
    {
        $checkResult = $this->getCheckResult(StoreCountryId::class);
        $this->assertTrue($checkResult->isValid());
        $this->assertSame('NL', $checkResult->getValue());
    }

    #[ConfigFixture('general/store_information/country_id', '', 'default')]
    #[ConfigFixture('general/store_information/country_id', 'NL', 'store', 'default')]
    public function testStoreCountryIdSetOnStoreViewIsValid(): void
    {
        $checkResult = $this->getCheckResult(StoreCountryId::class);
        $this->assertTrue($checkResult->isValid());
        $this->assertSame('NL', $checkResult->getValue());
    }

    #[DbIsolation(true)]
    public function testMerchantVatNumberSetOnWebsiteIsValid(): void
    {
        $this->saveConfig('general/store_information/merchant_vat_number', '', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        $this->saveConfig('general/store_information/merchant_vat_number', 'NL123456789B01', ScopeInterface::SCOPE_WEBSITES, $this->getWebsiteId());

        $checkResult = $this->getCheckResult(MerchantVatNumber::class);
        $this->assertTrue($checkResult->isValid());
        $this->assertSame('NL123456789B01', $checkResult->getValue());
    }

    #[DbIsolation(true)]
    public function testAddressLinesOverriddenOnWebsiteIsInvalid(): void
    {
        $this->saveConfig('customer/address/street_lines', '3', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        $this->saveConfig('customer/address/street_lines', '2', ScopeInterface::SCOPE_WEBSITES, $this->getWebsiteId());

        $checkResult = $this->getCheckResult(AddressLines::class);
        $this->assertFalse($checkResult->isValid());
        $this->assertSame('2', (string)$checkResult->getValue());
    }

    #[ConfigFixture('csp/mode/storefront_checkout_index_index/report_only', '1', 'store', 'default')]
    public function testCspMode(): void
    {
        $checkResult = $this->getCheckResult(CspMode::class);
        $this->assertSame('csp/mode/storefront_checkout_index_index/report_only', $checkResult->getLabel());
        $this->assertSame('1', (string)$checkResult->getValue());
        $this->assertTrue($checkResult->isValid());
    }

    protected function tearDown(): void
    {
        $this->reinitConfig();
    }

    private function getCheckResult(string $className): CheckResult
    {
        /** @var SystemCheckInterface $systemCheck */
        $systemCheck = ObjectManager::getInstance()->create($className);
        $checkResults = $systemCheck->getCheckResultCollection()->get();
        $this->assertCount(1, $checkResults);

        return $checkResults[0];
    }

    private function saveConfig(string $path, string $value, string $scope, int $scopeId): void
    {
        ObjectManager::getInstance()->get(WriterInterface::class)->save($path, $value, $scope, $scopeId);
        $this->reinitConfig();
    }

    private function reinitConfig(): void
    {
        $objectManager = ObjectManager::getInstance();
        $objectManager->get(ReinitableConfigInterface::class)->reinit();

        $scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        if (method_exists($scopeConfig, 'clean')) {
            $scopeConfig->clean();
        }
    }

    private function getWebsiteId(): int
    {
        return (int)ObjectManager::getInstance()->get(StoreManagerInterface::class)->getWebsite('base')->getId();
    }
}
