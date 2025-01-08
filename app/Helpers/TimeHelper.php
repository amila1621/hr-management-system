<?php

namespace App\Helpers;

class TimeHelper
{
    public static function sumTime($times)
    {
        $totalMinutes = 0;
        foreach ($times as $time) {
            $totalMinutes += self::parseTime($time);
        }
        return self::formatTime($totalMinutes);
    }

    public static function parseTime($time)
    {
        if (strpos($time, ':') !== false) {
            // HH:MM format
            list($hours, $minutes) = explode(':', $time);
            return $hours * 60 + $minutes;
        } else {
            // Decimal hours format
            return round($time * 60);
        }
    }

    public static function formatTime($minutes)
    {
        $hours = $minutes / 60;
        return number_format($hours, 2, '.', '');
    }

    public static function decimalToTime($decimal)
    {
        $hours = floor($decimal);
        $minutes = round(($decimal - $hours) * 60);
        return sprintf("%d:%02d", $hours, $minutes);
    }

    public static function timeToDecimal($time)
    {
        $minutes = self::parseTime($time);
        return round($minutes / 60, 2);
    }
}