<?php

declare(strict_types=1);

if (!function_exists('now')) {
    function now(DateTimeZone|string|null $tz = null): DateTime
    {
        $timezone = is_string($tz) ? new DateTimeZone($tz) : $tz;

        return new DateTime('now', $timezone);
    }
}