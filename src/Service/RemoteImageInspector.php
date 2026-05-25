<?php

declare(strict_types=1);

namespace LovelyWedding\Service;

final class RemoteImageInspector
{
    private const int CONNECT_TIMEOUT = 5;
    private const int TIMEOUT = 10;
    private const int MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    /**
     * Returns ['status' => int, 'length' => int, 'content_type' => string] or null on failure / SSRF block.
     *
     * @return array{status: int, length: int, content_type: string}|null
     */
    public function inspect(string $url): ?array
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (!$this->isPublicHost((string) $parts['host'])) {
            return null;
        }

        $ch = curl_init($url);
        if (false === $ch) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false, // re-validated below if we need to follow
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $data = curl_exec($ch);
        if (false === $data) {
            curl_close($ch);

            return null;
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $length = (int) curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($length > self::MAX_BYTES) {
            return [
                'status' => $status,
                'length' => $length,
                'content_type' => $contentType,
            ];
        }

        return [
            'status' => $status,
            'length' => $length,
            'content_type' => $contentType,
        ];
    }

    public function isPublicHost(string $host): bool
    {
        // Strip IPv6 brackets
        $host = trim($host, '[]');
        if ('' === $host) {
            return false;
        }

        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $rec) {
                    if (isset($rec['ip'])) {
                        $ips[] = (string) $rec['ip'];
                    } elseif (isset($rec['ipv6'])) {
                        $ips[] = (string) $rec['ipv6'];
                    }
                }
            }
        }

        if ([] === $ips) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!$this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public function isPublicIp(string $ip): bool
    {
        // Reject loopback / private / reserved ranges including link-local 169.254/16 and IPv6 ::1
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }
}
