<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\Test\Unit\Util;

use LokiCheckout\CoreAdminhtml\Util\MajorVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MajorVersionTest extends TestCase
{
    #[DataProvider('getKeyValues')]
    public function testGetKey(string $version, string $expected): void
    {
        $this->assertSame($expected, (new MajorVersion())->getKey($version));
    }

    public static function getKeyValues(): array
    {
        return [
            'major release' => ['2.1.0', '2'],
            'v-prefix' => ['v3.0.1', '3'],
            'patch suffix' => ['2.0.0-p1', '2'],
            '0.x release' => ['0.4.1', '0.4'],
            '0.x patch suffix' => ['0.5.0-p2', '0.5'],
            'only major' => ['0', '0.0'],
        ];
    }

    #[DataProvider('isNewerValues')]
    public function testIsNewer(string $version, string $compareVersion, bool $expected): void
    {
        $this->assertSame($expected, (new MajorVersion())->isNewer($version, $compareVersion));
    }

    public static function isNewerValues(): array
    {
        return [
            'new major' => ['2.0.0', '1.4.2', true],
            'same major' => ['2.2.0', '2.1.0', false],
            'older major' => ['1.9.0', '2.0.0', false],
            '0.x minor bump' => ['0.5.0', '0.4.3', true],
            '0.x patch bump' => ['0.4.9', '0.4.1', false],
            '0.x to 1.0' => ['1.0.0', '0.9.0', true],
            '0.10 after 0.9' => ['0.10.0', '0.9.5', true],
        ];
    }

    #[DataProvider('isDifferentValues')]
    public function testIsDifferent(string $version, string $compareVersion, bool $expected): void
    {
        $this->assertSame($expected, (new MajorVersion())->isDifferent($version, $compareVersion));
    }

    public static function isDifferentValues(): array
    {
        return [
            'different major' => ['1.0.0', '2.0.0', true],
            'same major' => ['2.0.0', '2.9.0', false],
            '0.x different minor' => ['0.4.0', '0.5.0', true],
            '0.x same minor' => ['0.4.1', '0.4.9', false],
        ];
    }
}
