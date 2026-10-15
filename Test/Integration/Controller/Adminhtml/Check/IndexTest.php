<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Controller\Adminhtml\Check;

use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\TestCase\AbstractBackendController;

#[AppArea('adminhtml')]
class IndexTest extends AbstractBackendController
{
    protected $resource = 'LokiCheckout_CoreAdminhtml::check';

    protected $uri = 'backend/loki_checkout/check/index';

    public function testPageRenders(): void
    {
        $this->dispatch($this->uri);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $body = $this->getResponse()->getBody();
        $this->assertStringContainsString('LokiCheckout - System Check', $body);
        $this->assertStringContainsString('loki-system-check', $body);
        $this->assertStringContainsString('Recommendation', $body);
        $this->assertStringContainsString('customer/address/street_lines', $body);
        $this->assertStringContainsString('general/store_information/country_id', $body);
        $this->assertStringContainsString('LokiCheckout_DevTools', $body);
    }

    #[ConfigFixture('customer/address/street_lines', '2', 'default')]
    #[ConfigFixture('general/store_information/merchant_vat_number', 'NL123456789B01', 'default')]
    #[ConfigFixture('general/store_information/country_id', 'NL', 'default')]
    public function testInvalidValueIsMarkedAsError(): void
    {
        $this->dispatch($this->uri);

        $this->assertMatchesRegularExpression(
            '/system-check-error">\s*2\s*</',
            $this->getResponse()->getBody()
        );
    }

    #[ConfigFixture('customer/address/street_lines', '3', 'default')]
    public function testValidValueIsMarkedAsNotice(): void
    {
        $this->dispatch($this->uri);

        $this->assertMatchesRegularExpression(
            '/system-check-notice">\s*3\s*</',
            $this->getResponse()->getBody()
        );
    }
}
