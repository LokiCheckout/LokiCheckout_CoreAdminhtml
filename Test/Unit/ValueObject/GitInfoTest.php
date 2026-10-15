<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ValueObject;

use LokiCheckout\CoreAdminhtml\ValueObject\GitInfo;
use PHPUnit\Framework\TestCase;

class GitInfoTest extends TestCase
{
    private const COMMIT = '0123456789abcdef0123456789abcdef01234567';

    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/loki-git-info-' . uniqid();
        mkdir($this->tempDir . '/module', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testWithoutGitFolder(): void
    {
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertFalse($gitInfo->hasInfo());
        $this->assertSame('', $gitInfo->getHeadContents());
        $this->assertSame('', $gitInfo->getCommit());
        $this->assertSame('', $gitInfo->getBranch());
    }

    public function testWithGitFolderInModule(): void
    {
        $this->writeFetchHead($this->tempDir . '/module', self::COMMIT . "\t\tbranch 'main' of https://example.com/foo\n");
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame(self::COMMIT, $gitInfo->getCommit());
        $this->assertSame('main', $gitInfo->getBranch());
    }

    public function testWithGitFolderInParent(): void
    {
        $this->writeFetchHead($this->tempDir, self::COMMIT . "\t\tbranch 'feature/foo' of https://example.com/foo\n");
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame(self::COMMIT, $gitInfo->getCommit());
        $this->assertSame('feature/foo', $gitInfo->getBranch());
    }

    public function testWithUnparsableFetchHead(): void
    {
        $this->writeFetchHead($this->tempDir . '/module', 'NOT A VALID FETCH HEAD');
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame('', $gitInfo->getCommit());
        $this->assertSame('', $gitInfo->getBranch());
    }

    private function writeFetchHead(string $path, string $contents): void
    {
        mkdir($path . '/.git', 0777, true);
        file_put_contents($path . '/.git/FETCH_HEAD', $contents);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;
            is_dir($itemPath) ? $this->removeDirectory($itemPath) : unlink($itemPath);
        }

        rmdir($path);
    }
}
