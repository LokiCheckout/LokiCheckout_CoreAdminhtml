<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\Cron;

use LokiCheckout\CoreAdminhtml\Cron\MajorVersionCheck;
use LokiCheckout\CoreAdminhtml\Util\MajorVersion;
use LokiCheckout\CoreAdminhtml\Util\MajorVersionChecker;
use LokiCheckout\CoreAdminhtml\ValueObject\MajorVersionUpdate;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Notification\NotifierInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class MajorVersionCheckTest extends TestCase
{
    private MajorVersionChecker&MockObject $checker;
    private NotifierInterface&MockObject $notifier;
    private FlagManager&MockObject $flagManager;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(MajorVersionChecker::class);
        $this->notifier = $this->createMock(NotifierInterface::class);
        $this->flagManager = $this->createMock(FlagManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testDoesNothingWhenDisabled(): void
    {
        $this->checker->expects($this->never())->method('getMajorUpdates');
        $this->notifier->expects($this->never())->method('addMajor');

        $this->createCron(false)->execute();
    }

    public function testDeletesFlagWhenNoUpdates(): void
    {
        $this->checker->method('getMajorUpdates')->willReturn([]);
        $this->notifier->expects($this->never())->method('addMajor');
        $this->flagManager->expects($this->once())->method('deleteFlag')->with(MajorVersionCheck::FLAG_CODE);

        $this->createCron()->execute();
    }

    public function testSkipsNotificationWhenHashUnchanged(): void
    {
        $this->checker->method('getMajorUpdates')->willReturn($this->updates());
        $this->flagManager->method('getFlagData')->willReturn(sha1('loki/foo:2'));
        $this->notifier->expects($this->never())->method('addMajor');
        $this->flagManager->expects($this->never())->method('saveFlag');

        $this->createCron()->execute();
    }

    public function testNotifiesWhenHashChanged(): void
    {
        $this->checker->method('getMajorUpdates')->willReturn($this->updates());
        $this->flagManager->method('getFlagData')->willReturn('old-hash');
        $this->notifier->expects($this->once())->method('addMajor')
            ->with(
                $this->anything(),
                $this->stringContains('loki/foo: 1.4.2 → 2.1.0')
            );
        $this->flagManager->expects($this->once())->method('saveFlag')
            ->with(MajorVersionCheck::FLAG_CODE, sha1('loki/foo:2'));

        $this->createCron()->execute();
    }

    public function testHashOnlyChangesWithMajorVersion(): void
    {
        $this->checker->method('getMajorUpdates')
            ->willReturn([new MajorVersionUpdate('loki/foo', '1.4.2', '2.2.0')]);
        $this->flagManager->method('getFlagData')->willReturn(sha1('loki/foo:2'));
        $this->notifier->expects($this->never())->method('addMajor');
        $this->flagManager->expects($this->never())->method('saveFlag');

        $this->createCron()->execute();
    }

    public function testHashUsesZeroMinorAsMajor(): void
    {
        $this->checker->method('getMajorUpdates')
            ->willReturn([new MajorVersionUpdate('loki/foo', '0.4.2', '0.5.1')]);
        $this->flagManager->method('getFlagData')->willReturn('old-hash');
        $this->flagManager->expects($this->once())->method('saveFlag')
            ->with(MajorVersionCheck::FLAG_CODE, sha1('loki/foo:0.5'));

        $this->createCron()->execute();
    }

    public function testDescriptionJoinsPackagesWithSemicolon(): void
    {
        $this->checker->method('getMajorUpdates')->willReturn([
            new MajorVersionUpdate('a/b', '1.4.2', '2.1.0'),
            new MajorVersionUpdate('c/d', '0.4.0', '0.5.0'),
        ]);
        $this->flagManager->method('getFlagData')->willReturn('old-hash');
        $this->notifier->expects($this->once())->method('addMajor')
            ->with(
                $this->anything(),
                'The following packages have a new major version available: a/b: 1.4.2 → 2.1.0; c/d: 0.4.0 → 0.5.0'
            );

        $this->createCron()->execute();
    }

    public function testSkipsWhenAdminNotificationDisabled(): void
    {
        $this->checker->expects($this->never())->method('getMajorUpdates');
        $this->notifier->expects($this->never())->method('addMajor');
        $this->flagManager->expects($this->never())->method('saveFlag');
        $this->flagManager->expects($this->never())->method('deleteFlag');

        $this->createCron(true, false)->execute();
    }

    public function testLogsErrorWhenCheckFails(): void
    {
        $this->checker->method('getMajorUpdates')->willThrowException(new RuntimeException('API down'));
        $this->logger->expects($this->once())->method('error');
        $this->notifier->expects($this->never())->method('addMajor');

        $this->createCron()->execute();
    }

    private function updates(): array
    {
        return [new MajorVersionUpdate('loki/foo', '1.4.2', '2.1.0')];
    }

    private function createCron(bool $enabled = true, bool $adminNotificationEnabled = true): MajorVersionCheck
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn($enabled);

        $moduleManager = $this->createStub(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturn($adminNotificationEnabled);

        return new MajorVersionCheck(
            $this->checker,
            $this->notifier,
            $this->flagManager,
            $scopeConfig,
            $this->logger,
            $moduleManager,
            new MajorVersion()
        );
    }
}
