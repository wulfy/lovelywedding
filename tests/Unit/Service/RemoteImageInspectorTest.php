<?php

declare(strict_types=1);

namespace LovelyWedding\Tests\Unit\Service;

use LovelyWedding\Service\RemoteImageInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RemoteImageInspectorTest extends TestCase
{
    private RemoteImageInspector $inspector;

    protected function setUp(): void
    {
        $this->inspector = new RemoteImageInspector();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function privateIpProvider(): array
    {
        return [
            'loopback ipv4'      => ['127.0.0.1'],
            'rfc1918 10/8'       => ['10.0.0.1'],
            'rfc1918 192.168/16' => ['192.168.1.1'],
            'rfc1918 172.16/12'  => ['172.16.0.5'],
            'link local'         => ['169.254.169.254'],
            'ipv6 loopback'      => ['::1'],
        ];
    }

    #[DataProvider('privateIpProvider')]
    public function testIsPublicIpRejectsPrivateRanges(string $ip): void
    {
        self::assertFalse($this->inspector->isPublicIp($ip));
    }

    public function testIsPublicIpAcceptsPublicIp(): void
    {
        self::assertTrue($this->inspector->isPublicIp('8.8.8.8'));
    }

    public function testIsPublicHostRejectsLoopbackHostname(): void
    {
        self::assertFalse($this->inspector->isPublicHost('localhost'));
    }

    public function testInspectRejectsNonHttpScheme(): void
    {
        self::assertNull($this->inspector->inspect('ftp://example.com/x.jpg'));
    }

    public function testInspectRejectsPrivateUrl(): void
    {
        self::assertNull($this->inspector->inspect('http://127.0.0.1/x.jpg'));
    }

    public function testInspectRejectsAwsMetadata(): void
    {
        self::assertNull($this->inspector->inspect('http://169.254.169.254/latest/meta-data/'));
    }
}
