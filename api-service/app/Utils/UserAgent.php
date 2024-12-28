<?php

namespace App\Utils;

class UserAgent
{
    private string $userAgent;

    public function __construct(string $userAgent)
    {
        $this->userAgent = $userAgent;
    }

    public function getPlatform(): string
    {
        // Detect Platform
        if (preg_match('/linux/i', $this->userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $this->userAgent)) {
            $platform = 'Mac';
        } elseif (preg_match('/windows|win32/i', $this->userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/iphone/i', $this->userAgent)) {
            $platform = 'iPhone';
        } elseif (preg_match('/android/i', $this->userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/ipad/i', $this->userAgent)) {
            $platform = 'iPad';
        } elseif (preg_match('/ipod/i', $this->userAgent)) {
            $platform = 'iPod';
        } elseif (preg_match('/blackberry/i', $this->userAgent)) {
            $platform = 'BlackBerry';
        } elseif (preg_match('/webos/i', $this->userAgent)) {
            $platform = 'Mobile';
        } else {
            $platform = 'Unknown';
        }

        return $platform;
    }

    public function getBrowser(): string
    {
        // Detect Browser
        if (preg_match('/MSIE/i', $this->userAgent) && ! preg_match('/Opera/i', $this->userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $this->userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/Chrome/i', $this->userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/Safari/i', $this->userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $this->userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/Netscape/i', $this->userAgent)) {
            $browser = 'Netscape';
        } else {
            $browser = 'Unknown';
        }

        return $browser;
    }
}
