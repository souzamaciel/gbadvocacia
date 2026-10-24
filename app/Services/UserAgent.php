<?php

namespace App\Services;

/**
 * Reads the browser, operating system and kind of device from a browser's User-Agent,
 * enough to tell in the activity log where an action came from.
 */
class UserAgent
{
    /**
     * @return array{browser: string|null, platform: string|null, device: string|null}
     */
    public static function parse(?string $userAgent): array
    {
        $agent = (string) $userAgent;

        if ($agent === '') {
            return ['browser' => null, 'platform' => null, 'device' => null];
        }

        $browsers = [
            'Edge' => '/Edg(?:e|A|iOS)?\/([\d]+)/',
            'Opera' => '/(?:OPR|Opera)\/([\d]+)/',
            'Samsung Internet' => '/SamsungBrowser\/([\d]+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/([\d]+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/([\d]+)/',
            'Safari' => '/Version\/([\d]+).*Safari/',
        ];
        $browser = null;
        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $agent, $match)) {
                $browser = $name.' '.$match[1];
                break;
            }
        }

        $platform = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod/', $agent) => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Mac OS X') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'CrOS') => 'ChromeOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        $device = match (true) {
            (bool) preg_match('/iPad|Tablet/', $agent) || (str_contains($agent, 'Android') && ! str_contains($agent, 'Mobile')) => 'Tablet',
            (bool) preg_match('/Mobile|iPhone|iPod/', $agent) => 'Celular',
            (bool) preg_match('/bot|crawl|spider|curl|wget/i', $agent) => 'Robô',
            default => 'Computador',
        };

        return ['browser' => $browser, 'platform' => $platform, 'device' => $device];
    }
}
