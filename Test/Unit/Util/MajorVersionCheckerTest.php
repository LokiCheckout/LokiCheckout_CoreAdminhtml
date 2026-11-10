<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\Util;

use LokiCheckout\Core\Util\Composer\ComposerApi;
use LokiCheckout\Core\Util\Composer\ComposerLock;
use LokiCheckout\CoreAdminhtml\Util\MajorVersionChecker;
use PHPUnit\Framework\TestCase;

class MajorVersionCheckerTest extends TestCase
{
    public function testDetectsNewMajorVersion(): void
    {
        $updates = $this->getUpdates(
            [['name' => 'loki/foo', 'version' => '1.4.2']],
            ['loki/foo' => $this->releases(['1.4.2', '2.0.0', '2.1.0', '1.5.0'])]
        );

        $this->assertCount(1, $updates);
        $this->assertSame('loki/foo', $updates[0]->getComposerName());
        $this->assertSame('1.4.2', $updates[0]->getInstalledVersion());
        $this->assertSame('2.1.0', $updates[0]->getLatestVersion());
    }

    public function testIgnoresSameMajorVersion(): void
    {
        $updates = $this->getUpdates(
            [['name' => 'loki/foo', 'version' => '2.0.0']],
            ['loki/foo' => $this->releases(['2.0.0', '2.3.1'])]
        );

        $this->assertSame([], $updates);
    }

    public function testSkipsDevVersions(): void
    {
        $updates = $this->getUpdates(
            [['name' => 'loki/foo', 'version' => 'dev-main']],
            ['loki/foo' => $this->releases(['3.0.0'])]
        );

        $this->assertSame([], $updates);
    }

    public function testIgnoresPreReleasesAndDevReleases(): void
    {
        $updates = $this->getUpdates(
            [['name' => 'loki/foo', 'version' => '1.0.0']],
            ['loki/foo' => $this->releases(['1.0.0', '2.0.0-beta1', '3.0.0-rc.1', 'dev-main#abc'])]
        );

        $this->assertSame([], $updates);
    }

    public function testSkipsPackagesNotAvailable(): void
    {
        $updates = $this->getUpdates(
            [['name' => 'vendor/other', 'version' => '1.0.0']],
            ['loki/foo' => $this->releases(['2.0.0'])]
        );

        $this->assertSame([], $updates);
    }

    private function getUpdates(array $lockPackages, array $availablePackages): array
    {
        $composerLock = $this->createStub(ComposerLock::class);
        $composerLock->method('getComposerLockData')->willReturn(['packages' => $lockPackages]);

        $composerApi = $this->createStub(ComposerApi::class);
        $composerApi->method('getPackages')->willReturn($availablePackages);

        return (new MajorVersionChecker($composerLock, $composerApi))->getMajorUpdates();
    }

    private function releases(array $versions): array
    {
        $releases = [];
        foreach ($versions as $version) {
            $releases[$version] = ['version' => $version];
        }

        return $releases;
    }
}
