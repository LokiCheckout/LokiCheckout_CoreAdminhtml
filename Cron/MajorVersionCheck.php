<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Cron;

use LokiCheckout\CoreAdminhtml\Util\MajorVersion;
use LokiCheckout\CoreAdminhtml\Util\MajorVersionChecker;
use LokiCheckout\CoreAdminhtml\ValueObject\MajorVersionUpdate;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Notification\NotifierInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class MajorVersionCheck
{
    public const CONFIG_PATH = 'loki_checkout/module_overview/major_version_check';

    public const FLAG_CODE = 'loki_checkout_major_version_check';

    public function __construct(
        private readonly MajorVersionChecker $majorVersionChecker,
        private readonly NotifierInterface $notifier,
        private readonly FlagManager $flagManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger,
        private readonly ModuleManager $moduleManager,
        private readonly MajorVersion $majorVersion,
    ) {
    }

    public function execute(): void
    {
        if (!$this->scopeConfig->isSetFlag(self::CONFIG_PATH)) {
            return;
        }

        if (!$this->moduleManager->isEnabled('Magento_AdminNotification')) {
            return;
        }

        try {
            $updates = $this->majorVersionChecker->getMajorUpdates();
        } catch (Throwable $exception) {
            $this->logger->error('Loki major version check failed: ' . $exception->getMessage());
            return;
        }

        if (empty($updates)) {
            $this->flagManager->deleteFlag(self::FLAG_CODE);
            return;
        }

        $hash = $this->getHash($updates);
        if ($this->flagManager->getFlagData(self::FLAG_CODE) === $hash) {
            return;
        }

        $this->notifier->addMajor(
            (string)__('New major versions available for installed Yireo/Loki packages'),
            $this->getDescription($updates)
        );

        $this->flagManager->saveFlag(self::FLAG_CODE, $hash);
    }

    /**
     * @param MajorVersionUpdate[] $updates
     */
    private function getHash(array $updates): string
    {
        $items = array_map(
            fn (MajorVersionUpdate $update) => $update->getComposerName() . ':'
                . $this->majorVersion->getKey($update->getLatestVersion()),
            $updates
        );
        sort($items);

        return sha1(implode(',', $items));
    }

    /**
     * @param MajorVersionUpdate[] $updates
     */
    private function getDescription(array $updates): string
    {
        $lines = [];
        foreach ($updates as $update) {
            $lines[] = $update->getComposerName() . ': '
                . $update->getInstalledVersion() . ' → ' . $update->getLatestVersion();
        }

        return (string)__('The following packages have a new major version available:')
            . ' ' . implode('; ', $lines);
    }
}
