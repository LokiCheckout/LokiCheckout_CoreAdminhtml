<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Acl;

use Magento\Backend\Model\Menu\Config as MenuConfig;
use Magento\Framework\Acl\AclResource\ProviderInterface;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

#[AppArea('adminhtml')]
class AclTest extends TestCase
{
    public function testAclResourcesAreNested(): void
    {
        $aclResources = ObjectManager::getInstance()->get(ProviderInterface::class)->getAclResources();
        $adminResource = $this->findResource($aclResources, 'Magento_Backend::admin');
        $this->assertNotNull($adminResource);

        $indexResource = $this->findResource($adminResource['children'], 'LokiCheckout_CoreAdminhtml::index');
        $this->assertNotNull($indexResource, 'Resource LokiCheckout_CoreAdminhtml::index not found');

        $this->assertNotNull($this->findResource($indexResource['children'], 'LokiCheckout_CoreAdminhtml::modules'));
        $this->assertNotNull($this->findResource($indexResource['children'], 'LokiCheckout_CoreAdminhtml::check'));
    }

    public function testMenuItemsExist(): void
    {
        $menu = ObjectManager::getInstance()->get(MenuConfig::class)->getMenu();

        $parent = $menu->get('LokiCheckout_CoreAdminhtml::index');
        $this->assertNotNull($parent);
        $this->assertSame('LokiCheckout_CoreAdminhtml::index', $parent->toArray()['resource']);
        $this->assertSame(
            $parent,
            $menu->get('Magento_Backend::system')->getChildren()->get('LokiCheckout_CoreAdminhtml::index')
        );

        $modules = $menu->get('LokiCheckout_CoreAdminhtml::modules');
        $this->assertNotNull($modules);
        $this->assertSame('LokiCheckout_CoreAdminhtml::modules', $modules->toArray()['resource']);
        $this->assertStringContainsString('loki_checkout/modules/index', $modules->getAction());

        $check = $menu->get('LokiCheckout_CoreAdminhtml::check');
        $this->assertNotNull($check);
        $this->assertSame('LokiCheckout_CoreAdminhtml::check', $check->toArray()['resource']);
        $this->assertStringContainsString('loki_checkout/check/index', $check->getAction());
    }

    private function findResource(array $resources, string $resourceId): ?array
    {
        foreach ($resources as $resource) {
            if ($resource['id'] === $resourceId) {
                return $resource;
            }
        }

        return null;
    }
}
