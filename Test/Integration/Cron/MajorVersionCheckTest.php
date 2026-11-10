<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Integration\Cron;

use LokiCheckout\CoreAdminhtml\Cron\MajorVersionCheck;
use Magento\Cron\Model\ConfigInterface as CronConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;

class MajorVersionCheckTest extends TestCase
{
    public function testCronJobIsRegistered(): void
    {
        $jobs = ObjectManager::getInstance()->get(CronConfig::class)->getJobs();
        $job = $jobs['default']['loki_checkout_major_version_check'] ?? null;

        $this->assertIsArray($job);
        $this->assertSame(MajorVersionCheck::class, ltrim($job['instance'], '\\'));
        $this->assertSame('execute', $job['method']);
        $this->assertSame('0 4 * * 1', $job['schedule']);
    }

    public function testCheckIsEnabledByDefault(): void
    {
        $scopeConfig = ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->assertSame('1', (string)$scopeConfig->getValue(MajorVersionCheck::CONFIG_PATH));
    }
}
