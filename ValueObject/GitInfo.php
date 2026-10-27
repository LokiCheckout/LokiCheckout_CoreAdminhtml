<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ValueObject;

class GitInfo
{
    public function __construct(
        private readonly string $path = ''
    ) {
    }

    public function hasInfo(): bool
    {
        return $this->getGitDir() !== '';
    }

    public function getBranch(): string
    {
        $ref = $this->getHeadRef();
        if (!str_starts_with($ref, 'refs/heads/')) {
            return '';
        }

        return substr($ref, strlen('refs/heads/'));
    }

    public function getCommit(): string
    {
        $contents = $this->getHeadContents();
        if ($this->isCommit($contents)) {
            return $contents;
        }

        $ref = $this->getHeadRef();
        if ($ref === '') {
            return '';
        }

        return $this->resolveRef($ref);
    }

    public function getHeadContents(): string
    {
        $gitDir = $this->getGitDir();
        if ($gitDir === '') {
            return '';
        }

        return trim((string)file_get_contents($gitDir . '/HEAD'), " \f\n\r\t\v\x00");
    }

    private function getHeadRef(): string
    {
        if (!preg_match('/^ref:\s*(\S+)$/', $this->getHeadContents(), $match)) {
            return '';
        }

        return $match[1];
    }

    private function resolveRef(string $ref): string
    {
        $commonDir = $this->getCommonDir();

        $refFile = $commonDir . '/' . $ref;
        if (is_file($refFile)) {
            $commit = trim((string)file_get_contents($refFile), " \f\n\r\t\v\x00");
            return $this->isCommit($commit) ? $commit : '';
        }

        $packedRefsFile = $commonDir . '/packed-refs';
        if (!is_file($packedRefsFile)) {
            return '';
        }

        foreach (file($packedRefsFile, FILE_IGNORE_NEW_LINES) as $line) {
            $parts = explode(' ', trim($line, " \f\n\r\t\v\x00"), 2);
            if (count($parts) === 2 && $parts[1] === $ref && $this->isCommit($parts[0])) {
                return $parts[0];
            }
        }

        return '';
    }

    private function getCommonDir(): string
    {
        $gitDir = $this->getGitDir();
        $commonDirFile = $gitDir . '/commondir';
        if (!is_file($commonDirFile)) {
            return $gitDir;
        }

        $commonDir = trim((string)file_get_contents($commonDirFile), " \f\n\r\t\v\x00");
        if (!str_starts_with($commonDir, '/')) {
            $commonDir = $gitDir . '/' . $commonDir;
        }

        return $commonDir;
    }

    private function getGitDir(): string
    {
        foreach ([$this->path, dirname($this->path)] as $path) {
            $gitDir = $this->findGitDir($path);
            if ($gitDir !== '') {
                return $gitDir;
            }
        }

        return '';
    }

    private function findGitDir(string $path): string
    {
        $git = $path . '/.git';
        if (is_dir($git)) {
            return is_file($git . '/HEAD') ? $git : '';
        }

        if (!is_file($git)) {
            return '';
        }

        // Worktrees and submodules use a .git file pointing to the actual git directory
        if (!preg_match('/^gitdir:\s*(.+)$/m', (string)file_get_contents($git), $match)) {
            return '';
        }

        $gitDir = trim($match[1], " \f\n\r\t\v\x00");
        if (!str_starts_with($gitDir, '/')) {
            $gitDir = $path . '/' . $gitDir;
        }

        return is_file($gitDir . '/HEAD') ? $gitDir : '';
    }

    private function isCommit(string $value): bool
    {
        return (bool)preg_match('/^[0-9a-f]{40}([0-9a-f]{24})?$/', $value);
    }
}
