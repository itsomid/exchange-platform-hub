<?php

namespace App\Services\Security;

use App\Mail\SuspiciousLoginMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

class SuspiciousLoginDetectorService
{
    /**
     * امتیاز ریسک آستانه برای ارسال هشدار
     */
    private const RISK_THRESHOLD = 50;

    /**
     * بررسی فعالیت‌های مشکوک در زمان ورود کاربر
     *
     * @param User $user کاربر وارد شده
     * @param Request $request درخواست HTTP
     * @return array نتایج بررسی و امتیاز ریسک
     */
    public function detectSuspiciousLogin(User $user, Request $request): array
    {
        $currentAgent = $request->userAgent();
        $currentIp = $request->ip();
        $lastToken = $user->tokens()->latest()->first();
        
        // اگر این اولین ورود کاربر است، فقط اطلاعات را ذخیره می‌کنیم
        if (!$lastToken) {
            return [
                'risk_score' => 0,
                'suspicious_activities' => [],
                'is_suspicious' => false,
            ];
        }
        
        $riskScore = 0;
        $suspiciousActivities = [];
        
        // بررسی تغییر User-Agent
        if ($this->isUserAgentChanged($lastToken, $currentAgent)) {
            $riskScore += 20;
            $suspiciousActivities[] = [
                'type' => 'user_agent_changed',
                'old_value' => $lastToken->user_agent,
                'new_value' => $currentAgent,
            ];
        }
        
        // بررسی تغییر آدرس IP
        if ($this->isIpChanged($lastToken, $currentIp)) {
            $riskScore += 30;
            $suspiciousActivities[] = [
                'type' => 'ip_changed',
                'old_value' => $lastToken->ip_address,
                'new_value' => $currentIp,
            ];
        }
        
        // بررسی زمان غیرمعمول ورود
        if ($this->isUnusualLoginTime($user)) {
            $riskScore += 25;
            $suspiciousActivities[] = [
                'type' => 'unusual_time',
                'time' => now()->format('H:i'),
            ];
        }
        
        // بررسی ورود همزمان از چند دستگاه
        if ($this->isMultipleActiveSession($user)) {
            $riskScore += 40;
            $suspiciousActivities[] = [
                'type' => 'multiple_sessions',
            ];
        }
        
        // بررسی تغییر موقعیت جغرافیایی (نیاز به سرویس GeoIP)
        // در صورت نیاز می‌توانید این بخش را فعال کنید
        /*
        if ($this->isLocationChanged($lastToken, $currentIp)) {
            $riskScore += 50;
            $suspiciousActivities[] = [
                'type' => 'location_changed',
                'old_location' => $this->getLocationFromIp($lastToken->ip_address),
                'new_location' => $this->getLocationFromIp($currentIp),
            ];
        }
        */
        
        $isSuspicious = $riskScore >= self::RISK_THRESHOLD;
        
        // اگر فعالیت مشکوک تشخیص داده شد، ایمیل هشدار ارسال می‌کنیم
        if ($isSuspicious) {
            $this->sendSuspiciousLoginAlert($user, $suspiciousActivities, $request);
        }
        
        return [
            'risk_score' => $riskScore,
            'suspicious_activities' => $suspiciousActivities,
            'is_suspicious' => $isSuspicious,
        ];
    }
    
    /**
     * بررسی تغییر User-Agent
     */
    private function isUserAgentChanged(?PersonalAccessToken $lastToken, string $currentAgent): bool
    {
        if (!$lastToken || !$lastToken->user_agent) {
            return false;
        }
        
        // بررسی تغییرات اساسی در User-Agent (نه فقط تغییر نسخه)
        $lastAgentParts = $this->parseUserAgent($lastToken->user_agent);
        $currentAgentParts = $this->parseUserAgent($currentAgent);
        
        // اگر مرورگر یا سیستم عامل تغییر کرده باشد
        return $lastAgentParts['browser'] !== $currentAgentParts['browser'] || 
               $lastAgentParts['os'] !== $currentAgentParts['os'];
    }
    
    /**
     * تجزیه User-Agent به اجزای اصلی آن
     */
    private function parseUserAgent(string $userAgent): array
    {
        $browser = 'unknown';
        $os = 'unknown';
        
        // تشخیص سیستم عامل
        if (preg_match('/windows|win32|win64/i', $userAgent)) {
            $os = 'windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'mac';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'android';
        } elseif (preg_match('/iphone|ipad/i', $userAgent)) {
            $os = 'ios';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'linux';
        }
        
        // تشخیص مرورگر
        if (preg_match('/MSIE|Trident|Edge/i', $userAgent)) {
            $browser = 'ie';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'opera';
        }
        
        return [
            'browser' => $browser,
            'os' => $os,
            'full' => $userAgent,
        ];
    }
    
    /**
     * بررسی تغییر آدرس IP
     */
    private function isIpChanged(?PersonalAccessToken $lastToken, string $currentIp): bool
    {
        if (!$lastToken || !$lastToken->ip_address) {
            return false;
        }
        
        return $lastToken->ip_address !== $currentIp;
    }
    
    /**
     * بررسی زمان غیرمعمول ورود
     */
    private function isUnusualLoginTime(User $user): bool
    {
        // ساعت فعلی
        $currentHour = (int) now()->format('H');
        
        // بررسی ورود در ساعات غیرمعمول (بین نیمه شب تا 5 صبح)
        if ($currentHour >= 0 && $currentHour <= 5) {
            // بررسی الگوی قبلی ورود کاربر
            $previousLogins = $user->tokens()
                ->whereNotNull('last_used_at')
                ->orderBy('last_used_at', 'desc')
                ->limit(5)
                ->get();
            
            // اگر کاربر معمولاً در این ساعات وارد نمی‌شود
            $unusualTime = true;
            foreach ($previousLogins as $login) {
                if ($login->last_used_at) {
                    $loginHour = (int) $login->last_used_at->format('H');
                    if ($loginHour >= 0 && $loginHour <= 5) {
                        $unusualTime = false;
                        break;
                    }
                }
            }
            
            return $unusualTime;
        }
        
        return false;
    }
    
    /**
     * بررسی ورود همزمان از چند دستگاه
     */
    private function isMultipleActiveSession(User $user): bool
    {
        // بررسی توکن‌های فعال در 24 ساعت گذشته
        $recentTokens = $user->tokens()
            ->where('last_used_at', '>=', now()->subDay())
            ->count();
        
        // اگر بیش از 3 دستگاه فعال وجود داشته باشد
        return $recentTokens >= 3;
    }
    
    /**
     * ارسال هشدار ورود مشکوک
     */
    private function sendSuspiciousLoginAlert(User $user, array $suspiciousActivities, Request $request): void
    {
        $data = [
            'user' => $user,
            'activities' => $suspiciousActivities,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'time' => now()->format('Y-m-d H:i:s'),
        ];
        
        Mail::to($user->email)->send(new SuspiciousLoginMail($data));
    }
}