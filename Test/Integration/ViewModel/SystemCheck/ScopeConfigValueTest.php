<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\AddressLines;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\CspMode;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\MerchantVatNumber;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\StoreCountryId;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use PHPUnit\Framework\TestCase;

#[AppArea('adminhtml')]
class ScopeConfigValueTest extends TestCase
{
    #[ConfigFixture('customer/address/street_lines', '2', 'default')]
    public function testAddressLinesInvalid(): void
    {
        $checkResult = $this->getCheckResult(AddressLines::class);
        $this->assertSame('customer/address/street_lines', $checkResult->getLabel());
        $this->assertFalse($checkResult->isValid());
        $this->assertStringContainsString('section/customer', (string)$checkResult->getUrl());
        $this->assertStringEndsWith('#customer_address', (string)$checkResult->getUrl());
    }

    #[ConfigFixture('customer/address/street_lines', '3', 'default')]
    public function testAddressLinesValid(): void
    {
        $this->assertTrue($this->getCheckResult(AddressLines::class)->isValid());
    }

    #[ConfigFixture('general/store_information/merchant_vat_number', '', 'default')]
    public function testMerchantVatNumberInvalid(): void
    {
        $this->assertFalse($this->getCheckResult(MerchantVatNumber::class)->isValid());
    }

    #[ConfigFixture('general/store_information/merchant_vat_number', 'NL123456789B01', 'default')]
    public function testMerchantVatNumberValid(): void
    {
        $checkResult = $this->getCheckResult(MerchantVatNumber::class);
        $this->assertTrue($checkResult->isValid());
        $this->assertSame('NL123456789B01', $checkResult->getValue());
    }

    #[ConfigFixture('general/store_information/country_id', '', 'default')]
    public function testStoreCountryIdInvalid(): void
    {
        $this->assertFalse($this->getCheckResult(StoreCountryId::class)->isValid());
    }

    #[ConfigFixture('general/store_information/country_id', 'NL', 'default')]
    public function testStoreCountryIdValid(): void
    {
        $checkResult = $this->getCheckResult(StoreCountryId::class);
        $this->assertTrue($checkResult->isValid());
        $this->assertSame('NL', $checkResult->getValue());
    }

    #[ConfigFixture('csp/mode/storefront_checkout_index_index/report_only', '1', 'default')]
    public function testCspMode(): void
    {
        $checkResult = $this->getCheckResult(CspMode::class);
        $this->assertSame('csp/mode/storefront_checkout_index_index/report_only', $checkResult->getLabel());
        $this->assertSame('1', (string)$checkResult->getValue());
        $this->assertTrue($checkResult->isValid());
    }

    private function getCheckResult(string $className): CheckResult
    {
        /** @var SystemCheckInterface $systemCheck */
        $systemCheck = ObjectManager::getInstance()->create($className);
        $checkResults = $systemCheck->getCheckResultCollection()->get();
        $this->assertCount(1, $checkResults);

        return $checkResults[0];
    }
}
