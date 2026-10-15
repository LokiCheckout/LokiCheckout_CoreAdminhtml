<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Controller\Adminhtml\Modules;

use LokiCheckout\Core\Util\Composer\ComposerApi;
use LokiCheckout\CoreAdminhtml\Test\Integration\Stub\ComposerApiStub;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\TestCase\AbstractBackendController;

#[AppArea('adminhtml')]
class IndexTest extends AbstractBackendController
{
    protected $resource = 'LokiCheckout_CoreAdminhtml::modules';

    protected $uri = 'backend/loki_checkout/modules/index';

    protected function setUp(): void
    {
        parent::setUp();
        $this->_objectManager->addSharedInstance(new ComposerApiStub(), ComposerApi::class);
    }

    protected function tearDown(): void
    {
        $this->_objectManager->removeSharedInstance(ComposerApi::class);
        parent::tearDown();
    }

    #[ConfigFixture('loki_checkout/module_overview/show_registered', '0', 'default')]
    public function testPageRenders(): void
    {
        $this->dispatch($this->uri);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $body = $this->getResponse()->getBody();
        $this->assertStringContainsString('LokiCheckout - Module Overview', $body);
        $this->assertMatchesRegularExpression('/\d+ modules found/', $body);
        $this->assertStringContainsString('LokiCheckout_CoreAdminhtml', $body);
        $this->assertStringContainsString(ComposerApiStub::COMPOSER_NAME, $body);
        $this->assertStringNotContainsString('LokiCheckout registered', $body);
    }

    #[ConfigFixture('loki_checkout/module_overview/show_registered', '1', 'default')]
    public function testRegisteredColumnIsShownWhenEnabled(): void
    {
        $this->dispatch($this->uri);

        $this->assertStringContainsString('LokiCheckout registered', $this->getResponse()->getBody());
    }
}
