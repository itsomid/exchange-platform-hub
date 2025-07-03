<?php

namespace App\Helpers;

use Carbon\Carbon;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

class DateFormatter
{
    public static function convertToPersianDate($timestamp, $format = '%A, %d %B %Y  H:i:s')
    {
        if (!$timestamp) {
            return 'N/A'; // Return an empty state instead of current time
        }
        return Jalalian::forge($timestamp)->format($format);
    }

    public static function ago($timestamp)
    {
        if (!$timestamp) {
            return 'N/A'; // Return an empty state instead of current time
        }
        return Jalalian::forge($timestamp)->ago();
    }
    public static function convertUnixTimeToPersianDate($timestamp, $addMinutes = 0, $format = '%A, %d %B %Y  H:i:s')
    {
         $date = Carbon::createFromTimestamp($timestamp, 'UTC') // Start in UTC
        ->addMinutes($addMinutes) // Adjust time if needed
        ->setTimezone('Asia/Tehran'); // Convert to Iran Standard Time

        return Jalalian::forge($date->toDateTimeString())->format($format);
    }
    public static function convertPersianToCarbonDate($moment)
    {
        $moment = str_replace('۰', 0, $moment);
        $moment = str_replace('۱', 1, $moment);
        $moment = str_replace('۲', 2, $moment);
        $moment = str_replace('۳', 3, $moment);
        $moment = str_replace('۴', 4, $moment);
        $moment = str_replace('۵', 5, $moment);
        $moment = str_replace('۶', 6, $moment);
        $moment = str_replace('۷', 7, $moment);
        $moment = str_replace('۸', 8, $moment);
        $moment = str_replace('۹', 9, $moment);

        $date = explode(' ', $moment)[0];
        $time = str_contains($moment, ' ')
            ? explode(' ', $moment)[1] : null;

        $date = explode('/', $date);
        $time = $time
            ? explode(':', $time)
            : ['00', '00'];

        $date = CalendarUtils::toGregorian($date[0], $date[1], $date[2]);
        $date = Carbon::create($date[0], $date[1], $date[2], $time[0], $time[1]);

        return (string) $date;
    }
    public static function timeUntilInPersian($date)
    {
        $now = Carbon::now();
        $target = Carbon::parse($date);

        if ($target->isPast()) {
            return 'منقضی شده'; // Handle past dates
        }

        $diffInSeconds = $now->diffInSeconds($target);

        if ($diffInSeconds < 3600) { // Less than an hour
            $diffInMinutes = $now->diffInMinutes($target); // Get whole minutes
            return self::convertToPersianNumbers($diffInMinutes) . ' دقیقه مانده';
        } elseif ($diffInSeconds < 86400) { // Less than a day
            $diffInHours = $now->diffInHours($target); // Get whole hours
            if ($diffInHours == 1) {
                return 'یک ساعت مانده';
            }
            return self::convertToPersianNumbers($diffInHours) . ' ساعت مانده';
        } else { // More than a day
            $diffInDays = $now->diffInDays($target); // Get whole days
            if ($diffInDays == 1) {
                return 'یک روز مانده';
            }
            return self::convertToPersianNumbers($diffInDays) . ' روز مانده';
        }
    }

    public static function convertToPersianNumbers($number)
    {
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($englishNumbers, $persianNumbers, floor($number));
    }

}
