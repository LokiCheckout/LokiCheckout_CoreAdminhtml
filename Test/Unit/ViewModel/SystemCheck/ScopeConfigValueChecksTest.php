<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollectionFactory;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\AddressLines;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\CspMode;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\MerchantVatNumber;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\ScopeConfigValue;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\StoreCountryId;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ScopeConfigValueChecksTest extends TestCase
{
    /**
     * @dataProvider getTestValues
     */
    public function testCheckResult(
        string $className,
        string $configPath,
        mixed $configValue,
        bool $expectedValid,
        string $expectedRecommendation
    ): void {
        $systemCheck = $this->createSystemCheck($className, $configPath, $configValue);
        $checkResults = $systemCheck->getCheckResultCollection()->get();

        $this->assertCount(1, $checkResults);
        $checkResult = $checkResults[0];
        $this->assertSame($configPath, $checkResult->getLabel());
        $this->assertSame($configValue, $checkResult->getValue());
        $this->assertSame($expectedValid, $checkResult->isValid());
        $this->assertStringContainsString($expectedRecommendation, $checkResult->getRecommendation());
    }

    public static function getTestValues(): array
    {
        return [
            'address lines 2' => [AddressLines::class, 'customer/address/street_lines', '2', false, '3 or 4'],
            'address lines 3' => [AddressLines::class, 'customer/address/street_lines', '3', true, '3 or 4'],
            'address lines 4' => [AddressLines::class, 'customer/address/street_lines', '4', true, '3 or 4'],
            'merchant vat empty' => [MerchantVatNumber::class, 'general/store_information/merchant_vat_number', '', false, 'VAT number'],
            'merchant vat null' => [MerchantVatNumber::class, 'general/store_information/merchant_vat_number', null, false, 'VAT number'],
            'merchant vat set' => [MerchantVatNumber::class, 'general/store_information/merchant_vat_number', 'NL123456789B01', true, 'VAT number'],
            'store country empty' => [StoreCountryId::class, 'general/store_information/country_id', '', false, 'country'],
            'store country set' => [StoreCountryId::class, 'general/store_information/country_id', 'NL', true, 'country'],
            'csp report only' => [CspMode::class, 'csp/mode/storefront_checkout_index_index/report_only', '1', false, 'CSP Strict Mode'],
            'csp strict' => [CspMode::class, 'csp/mode/storefront_checkout_index_index/report_only', '0', true, 'CSP Strict Mode'],
            'csp empty' => [CspMode::class, 'csp/mode/storefront_checkout_index_index/report_only', null, true, 'CSP Strict Mode'],
        ];
    }

    /**
     * @dataProvider getWarningValues
     */
    public function testWarning(string $className, string $configPath, mixed $configValue, bool $expectedWarning): void
    {
        $systemCheck = $this->createSystemCheck($className, $configPath, $configValue);
        $checkResult = $systemCheck->getCheckResultCollection()->get()[0];

        $this->assertSame($expectedWarning, $checkResult->isWarning());
    }

    public static function getWarningValues(): array
    {
        return [
            'csp report only' => [CspMode::class, 'csp/mode/storefront_checkout_index_index/report_only', '1', true],
            'csp strict' => [CspMode::class, 'csp/mode/storefront_checkout_index_index/report_only', '0', false],
            'address lines 2' => [AddressLines::class, 'customer/address/street_lines', '2', false],
            'merchant vat empty' => [MerchantVatNumber::class, 'general/store_information/merchant_vat_number', '', false],
            'store country empty' => [StoreCountryId::class, 'general/store_information/country_id', '', false],
        ];
    }

    public function testAddressLinesUrlPointsToCustomerAddressSection(): void
    {
        $systemCheck = $this->createSystemCheck(AddressLines::class, 'customer/address/street_lines', '3');

        $this->assertSame('https://example.com/admin/system_config/edit/section/customer/#customer_address', $systemCheck->getUrl());
        $checkResult = $systemCheck->getCheckResultCollection()->get()[0];
        $this->assertSame($systemCheck->getUrl(), $checkResult->getUrl());
    }

    /**
     * @dataProvider getClassesWithoutUrl
     */
    public function testOtherChecksHaveNoUrl(string $className): void
    {
        $systemCheck = $this->createSystemCheck($className, '', 'foo');
        $this->assertNull($systemCheck->getUrl());
    }

    public static function getClassesWithoutUrl(): array
    {
        return [
            [MerchantVatNumber::class],
            [StoreCountryId::class],
            [CspMode::class],
        ];
    }

    public function testCheckResultsAreNotDuplicatedWhenCalledTwice(): void
    {
        $systemCheck = $this->createSystemCheck(StoreCountryId::class, 'general/store_information/country_id', 'NL');

        $systemCheck->getCheckResultCollection();
        $this->assertCount(1, $systemCheck->getCheckResultCollection()->get());
    }

    public function testSingleResultWhenAllStoreViewsShareTheSameValue(): void
    {
        $systemCheck = $this->createSystemCheck(
            StoreCountryId::class,
            'general/store_information/country_id',
            '',
            ['nl' => 'NL', 'en' => 'NL']
        );

        $checkResults = $systemCheck->getCheckResultCollection()->get();
        $this->assertCount(1, $checkResults);
        $this->assertSame('general/store_information/country_id', $checkResults[0]->getLabel());
        $this->assertSame('NL', $checkResults[0]->getValue());
        $this->assertTrue($checkResults[0]->isValid());
    }

    public function testResultPerStoreViewWhenValuesDiffer(): void
    {
        $systemCheck = $this->createSystemCheck(
            StoreCountryId::class,
            'general/store_information/country_id',
            'NL',
            ['nl' => 'NL', 'en' => '']
        );

        $checkResults = $systemCheck->getCheckResultCollection()->get();
        $this->assertCount(2, $checkResults);
        $this->assertSame('general/store_information/country_id (nl)', $checkResults[0]->getLabel());
        $this->assertSame('NL', $checkResults[0]->getValue());
        $this->assertTrue($checkResults[0]->isValid());
        $this->assertSame('general/store_information/country_id (en)', $checkResults[1]->getLabel());
        $this->assertSame('', $checkResults[1]->getValue());
        $this->assertFalse($checkResults[1]->isValid());
    }

    /**
     * @param array<string, mixed> $storeValues Config values indexed by store code
     */
    private function createSystemCheck(
        string $className,
        string $configPath,
        mixed $configValue,
        array $storeValues = []
    ): ScopeConfigValue {
        $stores = [];
        $storeId = 1;
        $storeValuesById = [];
        foreach ($storeValues as $storeCode => $storeValue) {
            $store = $this->createMock(StoreInterface::class);
            $store->method('getId')->willReturn($storeId);
            $store->method('getCode')->willReturn($storeCode);
            $stores[] = $store;
            $storeValuesById[$storeId] = $storeValue;
            $storeId++;
        }

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function (string $path, string $scopeType = 'default', $scopeCode = null) use ($configPath, $configValue, $storeValuesById) {
                if ($path !== $configPath) {
                    return null;
                }

                if ($scopeType === ScopeInterface::SCOPE_STORE) {
                    return $storeValuesById[$scopeCode] ?? $configValue;
                }

                return $configValue;
            }
        );

        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')
            ->with('admin/system_config/edit', ['section' => 'customer'])
            ->willReturn('https://example.com/admin/system_config/edit/section/customer/');

        $urlFactory = $this->createMock(UrlFactory::class);
        $urlFactory->method('create')->willReturn($url);

        $checkResultCollectionFactory = $this->createMock(CheckResultCollectionFactory::class);
        $checkResultCollectionFactory->method('create')
            ->willReturnCallback(fn () => new CheckResultCollection());

        return new $className($scopeConfig, $storeManager, $urlFactory, $checkResultCollectionFactory);
    }
}
