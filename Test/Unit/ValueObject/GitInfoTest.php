<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\ValueObject;

use LokiCheckout\CoreAdminhtml\ValueObject\GitInfo;
use PHPUnit\Framework\TestCase;

class GitInfoTest extends TestCase
{
    private const COMMIT = '0123456789abcdef0123456789abcdef01234567';
    private const OTHER_COMMIT = 'fedcba9876543210fedcba9876543210fedcba98';

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

    public function testFreshCloneWithoutFetchHead(): void
    {
        $this->writeGitFile($this->tempDir . '/module', 'HEAD', "ref: refs/heads/main\n");
        $this->writeGitFile($this->tempDir . '/module', 'refs/heads/main', self::COMMIT . "\n");
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame(self::COMMIT, $gitInfo->getCommit());
        $this->assertSame('main', $gitInfo->getBranch());
    }

    public function testWithGitFolderInParent(): void
    {
        $this->writeGitFile($this->tempDir, 'HEAD', "ref: refs/heads/feature/foo\n");
        $this->writeGitFile($this->tempDir, 'refs/heads/feature/foo', self::COMMIT . "\n");
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame(self::COMMIT, $gitInfo->getCommit());
        $this->assertSame('feature/foo', $gitInfo->getBranch());
    }

    public function testCheckedOutBranchWinsOverFetchHead(): void
    {
        $path = $this->tempDir . '/module';
        $this->writeGitFile($path, 'HEAD', "ref: refs/heads/feature/x\n");
        $this->writeGitFile($path, 'refs/heads/feature/x', self::OTHER_COMMIT . "\n");
        $this->writeGitFile($path, 'FETCH_HEAD', self::COMMIT . "\t\tbranch 'main' of https://example.com/foo\n");
        $gitInfo = new GitInfo($path);

        $this->assertSame(self::OTHER_COMMIT, $gitInfo->getCommit());
        $this->assertSame('feature/x', $gitInfo->getBranch());
    }

    public function testRefFromPackedRefs(): void
    {
        $path = $this->tempDir . '/module';
        $this->writeGitFile($path, 'HEAD', "ref: refs/heads/main\n");
        $this->writeGitFile(
            $path,
            'packed-refs',
            "# pack-refs with: peeled fully-peeled sorted\n"
            . self::OTHER_COMMIT . " refs/heads/develop\n"
            . self::COMMIT . " refs/heads/main\n"
        );
        $gitInfo = new GitInfo($path);

        $this->assertSame(self::COMMIT, $gitInfo->getCommit());
        $this->assertSame('main', $gitInfo->getBranch());
    }

    public function testDetachedHead(): void
    {
        $this->writeGitFile($this->tempDir . '/module', 'HEAD', self::COMMIT . "\n");
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame(self::COMMIT, $gitInfo->getCommit());
        $this->assertSame('', $gitInfo->getBranch());
    }

    public function testGitFileForWorktree(): void
    {
        $mainGitDir = $this->tempDir . '/main/.git';
        $worktreeGitDir = $mainGitDir . '/worktrees/module';
        mkdir($worktreeGitDir . '/', 0777, true);
        file_put_contents($worktreeGitDir . '/HEAD', "ref: refs/heads/feature/y\n");
        file_put_contents($worktreeGitDir . '/commondir', "../..\n");
        mkdir($mainGitDir . '/refs/heads/feature', 0777, true);
        file_put_contents($mainGitDir . '/refs/heads/feature/y', self::COMMIT . "\n");
        file_put_contents($this->tempDir . '/module/.git', 'gitdir: ' . $worktreeGitDir . "\n");

        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame(self::COMMIT, $gitInfo->getCommit());
        $this->assertSame('feature/y', $gitInfo->getBranch());
    }

    public function testWithUnparsableHead(): void
    {
        $this->writeGitFile($this->tempDir . '/module', 'HEAD', 'NOT A VALID HEAD');
        $gitInfo = new GitInfo($this->tempDir . '/module');

        $this->assertTrue($gitInfo->hasInfo());
        $this->assertSame('', $gitInfo->getCommit());
        $this->assertSame('', $gitInfo->getBranch());
    }

    private function writeGitFile(string $path, string $file, string $contents): void
    {
        $filePath = $path . '/.git/' . $file;
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $contents);
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
